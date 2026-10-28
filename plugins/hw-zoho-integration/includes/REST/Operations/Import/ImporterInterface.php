<?php

namespace HWZoho\REST\Operations\Import;

interface ImporterInterface{

    public function create(array $data);

    public function update(array $data);

    public function delete(array $data);
}