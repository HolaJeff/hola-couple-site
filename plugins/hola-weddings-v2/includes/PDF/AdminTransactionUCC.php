<?php

namespace HW\PDF;

final class AdminTransactionUCC extends AbstractPDFDocument
{
    public function __construct(private int $transactionId)
    {
        parent::__construct();
    }

    protected function template(): string
    {
        return 'template-parts/pdf/admin-transaction-ucc';
    }

    protected function viewData(): array
    {
        $transaction = hw_get_transaction($this->transactionId);
        return ['transaction' => $transaction];
    }
}