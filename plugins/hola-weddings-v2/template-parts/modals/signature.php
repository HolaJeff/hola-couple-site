<!-- Signature Modal -->
<div id="signatureModal" class="modal fade" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= __('Create New Signature', 'hola-weddings') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="signature-wrapper">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- draw -->
                            <h5><?= __('Draw', 'hola-weddings') ?></h5>
                            <div data-placeholder class="canvas-placeholder border mb-3">
                                <canvas id="signature-canvas" width="400" height="200" ></canvas>
                                <div class="line pt-1 text-center text-black-50"><small><?= __('Sign here', 'hola-weddings') ?></small></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <!-- type -->
                            <h5><?= __('Type', 'hola-weddings') ?></h5>
                            <input type="text" placeholder="Type your signature" data-type-input class="form-control" />
                            <br>
                            <div class="font-selector d-flex">
                                <div class="font">
                                    <input id="signature-font-dancing" type="radio" name="signature-font-type" value="Dancing" checked />
                                    <label for="signature-font-dancing">
                                        <p class="placeholder dancing-font"><span><?= __('Signature', 'hola-weddings') ?></span></p>
                                    </label>
                                </div>
                                <div class="font">
                                    <input id="signature-font-pinyon" type="radio" name="signature-font-type" value="Pinyon" />
                                    <label class="font ml-3" for="signature-font-pinyon">
                                        <p class="placeholder pinyon-font"><span><?= __('Signature', 'hola-weddings') ?></span></p>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="options d-flex">
                        <button data-clear class="btn btn-primary" type="button"><?= __('Clear', 'hola-weddings') ?></button>
                        <button data-accept class="btn btn-success ml-auto" type="button"><?= __('Accept', 'hola-weddings') ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>