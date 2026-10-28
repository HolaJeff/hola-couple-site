<textarea name="additional_notes" cols="3" class="form-control <?= isset($errors['additional_notes']) ? 'is-invalid' : '' ?>"><?= $data['additional_notes'] ?></textarea>
<?php if(isset($errors['additional_notes'])): ?>
    <div id="additional-notes-Feedback" class="invalid-feedback">
        <?= $errors['additional_notes'] ?>
    </div>
<?php endif; ?>