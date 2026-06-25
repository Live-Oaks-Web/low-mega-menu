import { useEffect, useRef, useState, useCallback } from '@wordpress/element';
import { BaseControl, Button, TextareaControl } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';

/**
 * Lightweight WYSIWYG control.
 *
 * Visual mode is a contenteditable surface with a small formatting toolbar
 * (bold, italic, link, lists). A Code toggle swaps to a raw HTML textarea so
 * shortcodes / markup can be edited verbatim.
 *
 * @param {Object}   props
 * @param {string}   props.label       Field label.
 * @param {string}   props.value       Current HTML value.
 * @param {Function} props.onChange    Called with the new HTML string.
 * @param {string}   [props.help]      Optional help text.
 * @param {string}   [props.defaultMode] 'visual' (default) or 'code'.
 * @param {number}   [props.rows]      Rows for the code textarea.
 */
export default function WysiwygControl( {
	label,
	value,
	onChange,
	help,
	defaultMode = 'visual',
	rows = 6,
} ) {
	const instanceId = useInstanceId( WysiwygControl, 'low-mm-wysiwyg' );
	const [ mode, setMode ] = useState( defaultMode );
	const editorRef = useRef( null );
	const isFocused = useRef( false );

	const current = value || '';

	// Sync external value into the editable surface without clobbering the
	// caret while the user is actively typing.
	useEffect( () => {
		const el = editorRef.current;
		if ( mode !== 'visual' || ! el ) {
			return;
		}
		if ( ! isFocused.current && el.innerHTML !== current ) {
			el.innerHTML = current;
		}
	}, [ current, mode ] );

	const handleInput = useCallback( () => {
		const el = editorRef.current;
		if ( el ) {
			onChange( el.innerHTML );
		}
	}, [ onChange ] );

	const exec = useCallback(
		( command, arg = null ) => {
			const el = editorRef.current;
			if ( ! el ) {
				return;
			}
			el.focus();
			document.execCommand( command, false, arg );
			handleInput();
		},
		[ handleInput ]
	);

	const insertLink = useCallback( () => {
		// eslint-disable-next-line no-alert
		const url = window.prompt( 'Link URL', 'https://' );
		if ( url ) {
			exec( 'createLink', url );
		}
	}, [ exec ] );

	const toolbar = mode === 'visual' && (
		<div className="low-mm-wysiwyg__toolbar">
			<Button
				size="small"
				icon="editor-bold"
				label="Bold"
				showTooltip
				onClick={ () => exec( 'bold' ) }
			/>
			<Button
				size="small"
				icon="editor-italic"
				label="Italic"
				showTooltip
				onClick={ () => exec( 'italic' ) }
			/>
			<Button
				size="small"
				icon="admin-links"
				label="Insert link"
				showTooltip
				onClick={ insertLink }
			/>
			<Button
				size="small"
				icon="editor-unlink"
				label="Remove link"
				showTooltip
				onClick={ () => exec( 'unlink' ) }
			/>
			<Button
				size="small"
				icon="editor-ul"
				label="Bullet list"
				showTooltip
				onClick={ () => exec( 'insertUnorderedList' ) }
			/>
			<Button
				size="small"
				icon="editor-ol"
				label="Numbered list"
				showTooltip
				onClick={ () => exec( 'insertOrderedList' ) }
			/>
		</div>
	);

	return (
		<BaseControl
			id={ instanceId }
			label={ label }
			help={ help }
			__nextHasNoMarginBottom
		>
			<div className="low-mm-wysiwyg">
				<div className="low-mm-wysiwyg__bar">
					{ toolbar }
					<Button
						size="small"
						icon="editor-code"
						label="Edit HTML / code"
						showTooltip
						isPressed={ mode === 'code' }
						className="low-mm-wysiwyg__code-toggle"
						onClick={ () =>
							setMode( ( prev ) =>
								prev === 'code' ? 'visual' : 'code'
							)
						}
					/>
				</div>
				{ mode === 'code' ? (
					<TextareaControl
						__nextHasNoMarginBottom
						hideLabelFromVision
						label={ label }
						value={ current }
						onChange={ onChange }
						rows={ rows }
						className="low-mm-wysiwyg__code"
					/>
				) : (
					<div
						id={ instanceId }
						ref={ editorRef }
						className="low-mm-wysiwyg__editable"
						contentEditable
						suppressContentEditableWarning
						role="textbox"
						aria-multiline="true"
						aria-label={ label }
						onInput={ handleInput }
						onFocus={ () => {
							isFocused.current = true;
						} }
						onBlur={ () => {
							isFocused.current = false;
							handleInput();
						} }
					/>
				) }
			</div>
		</BaseControl>
	);
}
