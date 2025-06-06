<?php

/**
 * Handles language dropdowns.
 *
 * @since 1.0.0
 *
 */

namespace Hizzle\Noptin\Fields\Types;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles language dropdowns.
 *
 * @since 1.8.0
 */
class Language extends Dropdown {

	/**
	 * Retrieves the list of available languages.
	 *
	 * @since 1.8.0
	 * @return array
	 */
	public function get_languages() {
		$languages = noptin_get_available_languages();
		return is_array( $languages ) ? $languages : array();
	}

	/**
	 * Fetches available field options.
	 *
	 * @since 2.0.0
	 * @param array $custom_field
	 * @return array
	 */
	public function get_field_options( $custom_field ) {
		return $this->get_languages();
	}

	/**
	 * @inheritdoc
	 */
	public function output( $args ) {
		$args['options'] = $this->get_languages();
		parent::output( $args );
	}

	/**
	 * Sanitizes the submitted value.
	 *
	 * @since 1.8.0
	 * @param mixed $value Submitted value
	 */
	public function sanitize_value( $value ) {
		return '' === $value || array_key_exists( $value, $this->get_languages() ) ? $value : get_locale();
	}

	/**
	 * Filters the database schema.
	 *
	 * @since 2.0.0
	 * @param array $schema
	 * @param array $field
	 */
	public function filter_db_schema( $schema, $custom_field ) {

		// Call parent.
		$schema = parent::filter_db_schema( $schema, $custom_field );
		$column = $this->get_column_name( $custom_field );

		// Set default.
		if ( empty( $schema[ $column ]['default'] ) ) {
			$schema[ $column ]['default'] = get_locale();
		}

		return $schema;
	}
}
