import { isMobileViewport, mobileMediaQuery } from './constants';
import {
	searchEnabled,
	getSearchEndpoint,
	getRestNonce,
	getSearchMinChars,
	getString,
} from './config';
import { positionViewportPanel, resetDesktopPanelPosition } from './panel-position';

const DEBOUNCE_MS = 250;

/**
 * Wires a single search block: debounced AJAX, a desktop results mega panel, and
 * a relocate-into-drawer + expand experience on mobile.
 */
class SearchController {
	/**
	 * @param {HTMLElement} root The .low-mm-search element.
	 */
	constructor( root ) {
		this.root = root;
		this.form = root.querySelector( '.low-mm-search__form' );
		this.input = root.querySelector( '.low-mm-search__input' );
		this.panel = root.querySelector( '.low-mm-search__panel' );
		this.results = root.querySelector( '.low-mm-search__results' );
		this.backButton = root.querySelector( '.low-mm-search__back' );
		this.clearButton = root.querySelector( '.low-mm-search__clear' );
		this.container = root.closest( '.low-mm-nav-container' );

		/** @type {number|null} */
		this.debounceTimer = null;
		/** @type {AbortController|null} */
		this.fetchController = null;
		this.isPanelOpen = false;

		if ( ! this.input || ! this.panel || ! this.results ) {
			return;
		}

		// Marker so we can move the block back out of the drawer on desktop.
		this.homeSlot = document.createComment( 'low-mm-search-slot' );
		root.parentNode?.insertBefore( this.homeSlot, root.nextSibling );

		this.onInput = this.onInput.bind( this );
		this.onFocus = this.onFocus.bind( this );
		this.onKeydown = this.onKeydown.bind( this );
		this.onSubmit = this.onSubmit.bind( this );
		this.onBack = this.onBack.bind( this );
		this.onClear = this.onClear.bind( this );
		this.onDocumentClick = this.onDocumentClick.bind( this );

		this.input.addEventListener( 'input', this.onInput );
		this.input.addEventListener( 'focus', this.onFocus );
		this.input.addEventListener( 'keydown', this.onKeydown );
		this.form?.addEventListener( 'submit', this.onSubmit );
		this.backButton?.addEventListener( 'click', this.onBack );
		this.clearButton?.addEventListener( 'click', this.onClear );
		document.addEventListener( 'click', this.onDocumentClick );

		this.updateClearVisibility();
		this.applyPlacement();
	}

	/**
	 * @returns {HTMLElement}
	 */
	get anchor() {
		return this.container instanceof HTMLElement ? this.container : this.root;
	}

	/**
	 * Move the search block into the drawer on mobile, back to the nav on desktop.
	 */
	applyPlacement() {
		const drawerPanel = this.container?.querySelector( '.low-mm-mobile-drawer__panel' ) ?? null;

		if ( isMobileViewport() ) {
			if ( drawerPanel && this.root.parentNode !== drawerPanel ) {
				drawerPanel.insertBefore( this.root, drawerPanel.firstChild );
			}
			this.closePanel();
		} else {
			if ( this.homeSlot.parentNode && this.root.parentNode !== this.homeSlot.parentNode ) {
				this.homeSlot.parentNode.insertBefore( this.root, this.homeSlot );
			}
			this.deactivateMobile();
		}
	}

	onFocus() {
		if ( isMobileViewport() ) {
			this.activateMobile();
		}
	}

	activateMobile() {
		this.root.classList.add( 'low-mm-search--active' );
		this.container?.classList.add( 'low-mm-search-active' );
	}

	deactivateMobile() {
		this.root.classList.remove( 'low-mm-search--active' );
		this.container?.classList.remove( 'low-mm-search-active' );
	}

	onBack() {
		this.input.value = '';
		this.updateClearVisibility();
		this.clearResults();
		this.closePanel();
		this.deactivateMobile();
		this.input.blur();
	}

	/**
	 * Clear the field but keep the search open and focused.
	 */
	onClear() {
		this.input.value = '';
		this.updateClearVisibility();
		this.clearResults();
		this.closePanel();
		this.input.focus();
	}

	/**
	 * Show the clear button only when there is text to clear.
	 */
	updateClearVisibility() {
		if ( this.clearButton ) {
			this.clearButton.hidden = this.input.value.length === 0;
		}
	}

	/**
	 * @param {KeyboardEvent} event
	 */
	onKeydown( event ) {
		if ( event.key === 'Escape' ) {
			event.stopPropagation();
			this.onBack();
		}
	}

	/**
	 * Let Enter fall through to the native WordPress search results page.
	 *
	 * @param {SubmitEvent} event
	 */
	onSubmit( event ) {
		if ( this.input.value.trim().length < getSearchMinChars() ) {
			event.preventDefault();
		}
	}

	/**
	 * @param {MouseEvent} event
	 */
	onDocumentClick( event ) {
		if ( isMobileViewport() || ! this.isPanelOpen ) {
			return;
		}

		if ( event.target instanceof Node && this.root.contains( event.target ) ) {
			return;
		}

		this.closePanel();
	}

	onInput() {
		const value = this.input.value.trim();
		this.updateClearVisibility();

		if ( this.debounceTimer !== null ) {
			clearTimeout( this.debounceTimer );
		}

		if ( value.length < getSearchMinChars() ) {
			this.clearResults();
			this.closePanel();
			return;
		}

		this.debounceTimer = window.setTimeout( () => this.fetchResults( value ), DEBOUNCE_MS );
	}

	/**
	 * @param {string} query
	 */
	async fetchResults( query ) {
		const endpoint = getSearchEndpoint();
		if ( ! endpoint ) {
			return;
		}

		if ( this.fetchController ) {
			this.fetchController.abort();
		}
		this.fetchController = new AbortController();

		this.openPanel();
		this.renderStatus( getString( 'searchLoading', 'Searching…' ) );

		try {
			const response = await fetch( `${ endpoint }?q=${ encodeURIComponent( query ) }`, {
				headers: { 'X-WP-Nonce': getRestNonce() },
				signal: this.fetchController.signal,
			} );

			if ( ! response.ok ) {
				throw new Error( `HTTP ${ response.status }` );
			}

			const data = await response.json();
			this.renderResults( Array.isArray( data.results ) ? data.results : [] );
		} catch ( error ) {
			if ( error instanceof DOMException && error.name === 'AbortError' ) {
				return;
			}
			this.renderStatus( getString( 'searchError', 'Search failed. Please try again.' ) );
		}
	}

	/**
	 * @param {string} message
	 */
	renderStatus( message ) {
		this.clearResults();
		const status = document.createElement( 'div' );
		status.className = 'low-mm-search__status';
		status.textContent = message;
		this.results.appendChild( status );
	}

	/**
	 * @param {Array<Record<string, string>>} items
	 */
	renderResults( items ) {
		this.clearResults();

		if ( ! items.length ) {
			this.renderStatus( getString( 'searchNoResults', 'No results found.' ) );
			return;
		}

		const list = document.createElement( 'ul' );
		list.className = 'low-mm-search__list';

		items.forEach( ( item ) => {
			list.appendChild( this.buildItem( item ) );
		} );

		this.results.appendChild( list );
	}

	/**
	 * Build a single result node. Uses textContent / setAttribute to avoid
	 * injecting markup from the response.
	 *
	 * @param {Record<string, string>} item
	 * @returns {HTMLLIElement}
	 */
	buildItem( item ) {
		const li = document.createElement( 'li' );
		li.className = 'low-mm-search__item';

		const link = document.createElement( 'a' );
		link.className = 'low-mm-search__link';
		link.href = item.url || '#';

		if ( item.thumbnail ) {
			const media = document.createElement( 'span' );
			media.className = 'low-mm-search__thumb';
			const img = document.createElement( 'img' );
			img.src = item.thumbnail;
			img.alt = '';
			img.loading = 'lazy';
			media.appendChild( img );
			link.appendChild( media );
		}

		const body = document.createElement( 'span' );
		body.className = 'low-mm-search__body';

		const title = document.createElement( 'span' );
		title.className = 'low-mm-search__title';
		title.textContent = item.title || '';
		body.appendChild( title );

		if ( item.typeLabel ) {
			const meta = document.createElement( 'span' );
			meta.className = 'low-mm-search__type';
			meta.textContent = item.typeLabel;
			body.appendChild( meta );
		}

		if ( item.excerpt ) {
			const excerpt = document.createElement( 'span' );
			excerpt.className = 'low-mm-search__excerpt';
			excerpt.textContent = item.excerpt;
			body.appendChild( excerpt );
		}

		link.appendChild( body );
		li.appendChild( link );

		return li;
	}

	openPanel() {
		this.isPanelOpen = true;
		this.panel.hidden = false;
		this.container?.classList.add( 'low-mm-search-results-open' );

		if ( ! isMobileViewport() ) {
			positionViewportPanel( this.panel, this.anchor );
		}
	}

	closePanel() {
		this.isPanelOpen = false;
		this.panel.hidden = true;
		this.container?.classList.remove( 'low-mm-search-results-open' );
		resetDesktopPanelPosition( this.panel );
	}

	clearResults() {
		this.results.replaceChildren();
	}

	reposition() {
		if ( this.isPanelOpen && ! isMobileViewport() ) {
			positionViewportPanel( this.panel, this.anchor );
		}
	}
}

/**
 * Initialize all search blocks on the page.
 */
export function initSearch() {
	if ( ! searchEnabled() ) {
		return;
	}

	/** @type {SearchController[]} */
	const controllers = [];

	document.querySelectorAll( '.low-mm-search' ).forEach( ( node ) => {
		if ( node instanceof HTMLElement ) {
			controllers.push( new SearchController( node ) );
		}
	} );

	if ( ! controllers.length ) {
		return;
	}

	const mql = window.matchMedia( mobileMediaQuery );
	const onChange = () => controllers.forEach( ( controller ) => controller.applyPlacement() );

	if ( typeof mql.addEventListener === 'function' ) {
		mql.addEventListener( 'change', onChange );
	} else if ( typeof mql.addListener === 'function' ) {
		mql.addListener( onChange );
	}

	window.addEventListener(
		'resize',
		() => controllers.forEach( ( controller ) => controller.reposition() ),
		{ passive: true }
	);
}
