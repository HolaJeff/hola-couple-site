<?php

namespace HW\PDF;

use Spipu\Html2Pdf\Html2Pdf;

abstract class AbstractPDFDocument
{
    protected Html2Pdf $pdf;

    /**
     * constructor
     *
     * @param array $pdf_args
     */
    public function __construct( null|array $pdf_args = [])
    {

        // merge arguments
        $args = wp_parse_args($pdf_args, $this->getDefaultPDFArguments());

        // Allow injecting for tests or custom config
        $this->pdf = $pdf ?? new Html2Pdf( $args );
    }

    // Each document defines where HTML comes from
    abstract protected function template(): string;

    // Each document defines what data is passed to the template
    abstract protected function viewData(): array;

    /**
     * return default arguments
     *
     * @return array
     */
    protected function getDefaultPDFArguments(): array{

        return [
            'P', 
            'A4', 
            'en',
            true, 
            'UTF-8', 
            [20, 20, 20, 22]
        ];
    }

    /**
     * render the PDF template
     *
     * @return string
     */
    public function html(): string
    {
        ob_start();
        hw_get_template($this->template(), $this->viewData());
        return (string) ob_get_clean();
    }

    /**
     * output the pdf. Destination options are 'I' inline, 'D' download, 'F' file, 'S' string
     *
     * @param string $name
     * @param string $dest
     * @return void
     */
    public function output(string $name = 'document.pdf', string $dest = 'I')
    {
        $this->pdf->writeHTML($this->html());

        return $this->pdf->output($name, $dest);
    }

    /**
     * save to file
     *
     * @param string $absolutePath
     * @return void
     */
    public function saveTo(string $absolutePath): void
    {
        $this->pdf->output($absolutePath, 'F');
    }
}