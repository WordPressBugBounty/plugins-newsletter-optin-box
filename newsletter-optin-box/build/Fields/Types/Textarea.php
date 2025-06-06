<?php
/**
 * Handles textarea inputs.
 *
 * @since 1.0.0
 *
 */

namespace Hizzle\Noptin\Fields\Types;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles textarea inputs.
 *
 * @since 1.5.5
 */
class Textarea extends Base {

	/**
	 * @inheritdoc
	 */
	public function output( $args ) {

		$placeholder        = empty( $args['placeholder'] ) ? $args['label'] : $args['placeholder'];
		$has_no_placeholder = empty( $args['placeholder'] ) || $placeholder === $args['label'];
		?>

			<label class="noptin-label" for="<?php echo esc_attr( $args['id'] ); ?>"><?php echo empty( $args['react'] ) ? wp_kses_post( $args['label'] ) : '{{field.type.label}}'; ?></label>
			<textarea
				name="<?php echo esc_attr( $args['name'] ); ?>"
				id="<?php echo esc_attr( $args['id'] ); ?>"
				class="noptin-text noptin-form-field <?php echo $has_no_placeholder ? 'noptin-form-field__has-no-placeholder' : 'noptin-form-field__has-placeholder'; ?>"
				rows="4"
				<?php if ( empty( $args['react'] ) ) : ?>
					placeholder="<?php echo esc_attr( $placeholder ); ?>"
				<?php else : ?>
					:placeholder="field.type.label"
				<?php endif; ?>
			><?php echo isset( $args['value'] ) ? esc_textarea( $args['value'] ) : ''; ?></textarea>
		<?php

	}

}
