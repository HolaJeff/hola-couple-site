<?php

namespace HW\UI\Forms\Controls;

/**
 * Password input that never pre-fills with the stored value.
 *
 * The field always renders blank. When a value is already saved server-side,
 * a placeholder and a confirmation note are shown. A blank submission must be
 * treated as "keep existing" by the server handler — this control does not
 * enforce that; the caller is responsible.
 */
class Password extends FormControl {

	protected bool   $hasSavedValue    = false;
	protected string $savedPlaceholder = '';

	protected function getType(): string {
		return 'password';
	}

	/**
	 * Mark that a value is already saved. When true the field shows a
	 * "saved — leave blank to keep" placeholder and green confirmation note.
	 */
	public function saved( bool $saved = true ): static {
		$this->hasSavedValue = $saved;
		return $this;
	}

	/**
	 * Override the placeholder shown when a value is already saved.
	 * Defaults to a generic "enter a new value to replace it" message.
	 */
	public function savedPlaceholder( string $text ): static {
		$this->savedPlaceholder = $text;
		return $this;
	}

	protected function renderControl(): void {

		if ( $this->hasSavedValue ) {
			$placeholder = $this->savedPlaceholder
				?: __( 'Value saved — enter a new value to replace it', 'hola-wedding' );
		} else {
			$placeholder = $this->getPlaceholder();
		}
		?>
		<input
			class="form-control <?= implode( ' ', $this->arguments['class'] ?? [] ) ?>"
			name="<?= esc_attr( $this->getName() ) ?>"
			id="<?= esc_attr( $this->getId() ) ?>"
			type="password"
			value=""
			autocomplete="new-password"
			placeholder="<?= esc_attr( $placeholder ) ?>"
			<?php renderAttributes( $this->getAttributes() ) ?>
		>
		<?php if ( $this->hasSavedValue ) : ?>
			<small class="form-text" style="color:#2e7d32">
				<i class="fas fa-check-circle"></i>
				<?php esc_html_e( 'A value is saved. Leave blank to keep it.', 'hola-wedding' ); ?>
			</small>
		<?php endif; ?>
		<?php
	}

	/** Expose getPlaceholder() since Text defines it but we extend FormControl directly. */
	protected string $placeholder = '';

	public function placeholder( string $value ): static {
		$this->placeholder = $value;
		return $this;
	}

	public function getPlaceholder(): string {
		return $this->placeholder;
	}
}
