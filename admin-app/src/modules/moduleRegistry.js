import LinkListFields from '../modules/LinkListFields';
import ExcerptFields from '../modules/ExcerptFields';
import PostQueryFields from '../modules/PostQueryFields';
import ImageFields from '../modules/ImageFields';
import CtaFields from '../modules/CtaFields';
import CodeFields from '../modules/CodeFields';
import ScrollToFields from '../modules/ScrollToFields';

const registry = {
	link_list: {
		label: 'Link List',
		component: LinkListFields,
		defaultSettings: {
			description_plain_text_only: false,
			rows: [],
		},
	},
	excerpt: {
		label: 'Page / Post Excerpt',
		component: ExcerptFields,
		defaultSettings: {
			source_post_id: 0,
			show_image: true,
			show_excerpt: true,
			custom_excerpt: '',
			excerpt_length: 0,
			rich_text_override: false,
		},
	},
	post_query: {
		label: 'Post Query',
		component: PostQueryFields,
		defaultSettings: {
			post_type: 'post',
			taxonomy: '',
			term_id: 0,
			sort: 'newest',
			count: 5,
			offset: 0,
			show_image: true,
			show_date: true,
			show_category_label: true,
			show_excerpt: false,
			view_all_label: '',
			view_all_url: '',
		},
	},
	image: {
		label: 'Image',
		component: ImageFields,
		defaultSettings: {
			attachment_id: 0,
			alt_text: '',
			link_url: '',
			open_in_new_tab: false,
		},
	},
	cta: {
		label: 'Call to Action',
		component: CtaFields,
		defaultSettings: {
			heading: '',
			body: '',
			body_plain_text_only: false,
			text_color: '',
			button_label: '',
			button_url: '',
			button_text_color: '',
			button_background_color: '',
			background_mode: 'color',
			background_color: '#f5f5f5',
			background_image_id: 0,
			alignment: 'left',
		},
	},
	code: {
		label: 'Code / Shortcode',
		component: CodeFields,
		defaultSettings: {
			content: '',
			shortcode_execution: 'inherit',
		},
	},
	scroll_to: {
		label: 'Scroll To',
		component: ScrollToFields,
		defaultSettings: {
			post_type: 'page',
			source_post_id: 0,
			heading_index: -1,
			title: '',
			content: '',
		},
	},
};

export function getModuleRegistry() {
	const localized = window.lowMmBuilderData?.moduleTypes || [];
	const merged = { ...registry };

	localized.forEach( ( entry ) => {
		if ( merged[ entry.type ] ) {
			merged[ entry.type ].label = entry.label;
		}
	} );

	return merged;
}

export function getModuleDefinition( type ) {
	return getModuleRegistry()[ type ] || null;
}

export function getPaletteItems() {
	return Object.entries( getModuleRegistry() ).map( ( [ type, def ] ) => ( {
		type,
		label: def.label,
	} ) );
}

export function getDefaultSettings( type ) {
	const def = getModuleDefinition( type );
	return def ? { ...def.defaultSettings } : {};
}
