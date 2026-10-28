<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header px-4">
                <h5 class="modal-title" id="exampleModalLabel">
                    <i class="mr-1" data-feather="log-in"></i>
                    <?= __('Sign-in', 'hola-weddings') ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body px-4 pb-4 accordion">
                <div id="ajax-login" class="collapse open show" data-parent="#loginModal">
                    <div class="form-group">
                        <label for="username"><?= __('Email', 'hola-weddings') ?></label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Email" />
                        <div id="ajax-login-username-Feedback" class="invalid-feedback"></div>
                    </div>
                    <div class="form-group">
                        <label for="passowrd"><?= __('Password', 'hola-weddings') ?></label>
                        <input type="password" class="form-control" id="passowrd" name="password" placeholder="Password" />
                        <div id="ajax-login-password-Feedback" class="invalid-feedback"></div>
                    </div>
                    <p>
                        <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#ajax-reset-password">
                            <?= __('Forgot your password?', 'hola-weddings') ?>
                        </button>
                    </p>
                    <button type="button" class="ajax-login-btn btn btn-info w-100"><?= __('Log In', 'hola-weddings') ?></button>
                </div>
                <div id="ajax-reset-password" class="collapse" data-parent="#loginModal">
                    <div class="form-group">
                        <label for="email"><?= __('Email Address', 'hola-weddings') ?></label>
                        <input type="text" class="form-control" id="email" name="email" placeholder="Email" />
                        <div id="ajax-reset-password-Feedback" class="invalid-feedback"></div>
                    </div>
                    <p>
                        <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#ajax-login">
                            <?= __('Go to Sign-in', 'hola-weddings') ?>
                        </button>
                    </p>
                    <button type="button" class="reset-password-btn btn btn-info w-100"><?= __('Reset Password', 'hola-weddings') ?></button>
                </div>
            </div>
        </div>
    </div>
</div>