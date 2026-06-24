import { getConfig } from './config';
import { closeNavUi } from './nav-ui';
import { resetDesktopPanelPosition } from './panel-position';

/** Pixels above the target heading when scrolling. */
const SCROLL_TO_OFFSET_PX = 30;

/** @type {string[]} */
const CONTENT_ROOT_SELECTORS = [
	'.et_pb_post_content',
	'.entry-content',
	'.post-content',
	'#main-content .et_builder_inner_content',
	'article.type-post',
	'article.type-page',
	'#main-content',
	'main',
];

/**
 * @returns {number}
 */
function getScrollToOffset() {
	const configured = getConfig().scrollToOffset;
	return typeof configured === 'number' && configured >= 0 ? configured : SCROLL_TO_OFFSET_PX;
}

/**
 * @param {string} path
 * @returns {string}
 */
function normalizePath( path ) {
	const normalized = path.replace( /\/$/, '' );
	return normalized || '/';
}

/**
 * @param {HTMLAnchorElement} link
 * @param {URL} url
 * @returns {boolean}
 */
function isSamePageTarget( link, url ) {
	const href = link.getAttribute( 'href' ) || '';

	if ( href.startsWith( '#' ) ) {
		return true;
	}

	const targetPostId = parseInt( link.getAttribute( 'data-low-mm-scroll-post' ) || '0', 10 );
	const currentPostId = getConfig().singularPostId || 0;

	if ( targetPostId > 0 && currentPostId > 0 && targetPostId === currentPostId ) {
		return true;
	}

	return (
		url.origin === window.location.origin &&
		normalizePath( url.pathname ) === normalizePath( window.location.pathname )
	);
}

/**
 * @param {HTMLAnchorElement} link
 * @param {URL} url
 * @returns {string}
 */
function getScrollHash( link, url ) {
	if ( url.hash ) {
		return url.hash;
	}

	const href = link.getAttribute( 'href' ) || '';
	const hashIndex = href.indexOf( '#' );

	return hashIndex >= 0 ? href.slice( hashIndex ) : '';
}

/**
 * @returns {HTMLElement|null}
 */
function findContentRoot() {
	for ( const selector of CONTENT_ROOT_SELECTORS ) {
		const node = document.querySelector( selector );
		if ( node instanceof HTMLElement ) {
			return node;
		}
	}

	return document.querySelector( 'main' ) instanceof HTMLElement
		? document.querySelector( 'main' )
		: null;
}

/**
 * Immediately hide the panel wrapping this link (before global nav close).
 *
 * @param {HTMLAnchorElement} link
 */
function closePanelContaining( link ) {
	const panel = link.closest( '.low-mm-panel' );

	if ( panel instanceof HTMLElement && ! panel.hasAttribute( 'hidden' ) ) {
		resetDesktopPanelPosition( panel );
		panel.setAttribute( 'hidden', '' );
		panel.classList.remove( 'low-mm-panel-open', 'is-open' );
		panel.removeAttribute( 'aria-modal' );
	}

	link
		.closest( '.low-mm-has-panel, .low-mm-nav-item--has-panel' )
		?.classList.remove( 'low-mm-trigger-active', 'low-mm-drill-active' );
}

/**
 * Assign stable anchor ids to headings in the current post content.
 *
 * @param {number} postId
 */
function annotateHeadings( postId ) {
	const root = findContentRoot();
	if ( ! root || postId <= 0 ) {
		return;
	}

	const offset = getScrollToOffset();
	let index = 0;

	root.querySelectorAll( 'h1, h2, h3, h4, h5, h6' ).forEach( ( heading ) => {
		if ( ! ( heading instanceof HTMLElement ) ) {
			return;
		}

		const text = heading.textContent?.trim();
		if ( ! text ) {
			return;
		}

		const anchorId = `low-mm-heading-${ postId }-${ index }`;

		heading.id = anchorId;
		heading.setAttribute( 'data-low-mm-heading-index', String( index ) );
		heading.style.scrollMarginTop = `${ offset }px`;
		index += 1;
	} );
}

/**
 * @param {string} hash
 */
function scrollToHash( hash ) {
	const id = hash.replace( /^#/, '' );
	if ( ! id ) {
		return;
	}

	const match = id.match( /^low-mm-heading-(\d+)-(\d+)$/ );

	if ( match ) {
		annotateHeadings( parseInt( match[ 1 ], 10 ) );
	}

	let target = document.getElementById( id );
	if ( ! ( target instanceof HTMLElement ) && match ) {
		const root = findContentRoot();
		const index = parseInt( match[ 2 ], 10 );
		target = root?.querySelector( `[data-low-mm-heading-index="${ index }"]` ) ?? null;
		if ( target instanceof HTMLElement && ! target.id ) {
			target.id = id;
		}
	}

	if ( ! ( target instanceof HTMLElement ) ) {
		return;
	}

	const offset = getScrollToOffset();
	const top = target.getBoundingClientRect().top + window.scrollY - offset;

	window.scrollTo( { top: Math.max( 0, top ), behavior: 'smooth' } );
	target.setAttribute( 'tabindex', '-1' );
	target.focus( { preventScroll: true } );
}

/**
 * @param {MouseEvent} event
 */
function handleClick( event ) {
	const link = event.target instanceof Element ? event.target.closest( '.low-mm-scroll-to__link' ) : null;
	if ( ! ( link instanceof HTMLAnchorElement ) ) {
		return;
	}

	let url;
	try {
		url = new URL( link.href, window.location.href );
	} catch {
		return;
	}

	const hash = getScrollHash( link, url );
	if ( ! hash.startsWith( '#low-mm-heading-' ) ) {
		return;
	}

	const samePage = isSamePageTarget( link, url );

	event.preventDefault();
	event.stopPropagation();

	closePanelContaining( link );
	closeNavUi();

	if ( samePage ) {
		requestAnimationFrame( () => {
			requestAnimationFrame( () => {
				scrollToHash( hash );
			} );
		} );
		return;
	}

	window.location.assign( url.href );
}

/**
 * Initialize scroll-to heading anchors and in-page navigation.
 */
export function initScrollTo() {
	const postId = getConfig().singularPostId || 0;
	if ( postId > 0 ) {
		annotateHeadings( postId );
	}

	if ( window.location.hash.startsWith( '#low-mm-heading-' ) ) {
		requestAnimationFrame( () => {
			scrollToHash( window.location.hash );
		} );
	}

	document.addEventListener( 'click', handleClick, true );
}
