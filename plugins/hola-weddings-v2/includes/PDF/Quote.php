<?php

namespace HW\PDF;

final class QuotePdf extends AbstractPdfDocument
{
    public function __construct(private int $quoteId)
    {
        parent::__construct();
    }

    protected function template(): string
    {
        return 'template-parts/pdf/quote';
    }

    protected function viewData(): array
    {
        return ['id' => $this->quoteId, 'quote' => hw_get_quote($this->quoteId)];
    }
}