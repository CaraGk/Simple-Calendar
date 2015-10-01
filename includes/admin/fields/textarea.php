<?php
/**
 * Textarea Field
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Fields;

use SimpleCalendar\Abstracts\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Textarea input field.
 */
class Textarea extends Field {

	/**
	 * Construct.
	 *
	 * @param array $field
	 */
	public function __construct( $field ) {

		$this->type_class = 'simcal-field-textarea';

		parent::__construct( $field );

		if ( ! empty( $field['value' ] ) ) {
			$this->value = esc_textarea( $field['value'] );
		}
		if ( ! empty( $field['default'] ) ) {
			$this->default = esc_textarea( $field['default'] );
		}
	}

	/**
	 * Outputs the field markup.
	 */
	public function html() {

		if ( 'metabox' != $this->context ) {
			echo $this->tooltip;
		}

		?>
		<textarea
			name="<?php echo $this->name; ?>"
			id="<?php echo $this->id; ?>"
			<?php
			echo $this->class ? 'class="'  . $this->class . '" ' : '';
			echo $this->placeholder ? 'placeholder="'  . $this->placeholder . '" ' : '';
			echo $this->style ? 'style="'  . $this->style . '" ' : '';
			echo $this->attributes;
			?>><?php echo $this->value;  ?></textarea>
		<?php

		if ( 'metabox' == $this->context ) {
			echo $this->tooltip;
		}

		if ( ! empty( $this->description ) ) {
			echo '<p class="description">' . wp_kses_post( $this->description ) . '</p>';
		}

	}

}
