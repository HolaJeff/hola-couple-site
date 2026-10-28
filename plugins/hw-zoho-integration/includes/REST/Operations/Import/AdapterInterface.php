<?php

namespace HWZoho\REST\Operations\Import;

interface AdapterInterface{

    public function getPostData(array $data);

    public function getMetaData(array $data);
}