<?php

namespace HWZoho\AsyncRequests;

use com\zoho\api\logger\SDKLogger;
use com\zoho\crm\api\exception\SDKException;
use HWZoho\API\Auth\OAuth;
use HWZoho\API\CRM\Adapters\Invoices;
use HWZoho\API\CRM\Modules\Payments;
use HWZoho\Traits\Singleton;

class UpdateRecord extends \WP_Async_Request{

    use Singleton;

    /**
	 * @var string
	 */
	protected $prefix = 'hwzi';

	/**
	 * @var string
	 */
	protected $action = 'update_record';

	/**
	 * module name
	 *
	 * @var string
	 */
	protected $moduleName;

	/**
	 * record id
	 *
	 * @var stirng
	 */
	protected $recordId;

	/**
	 * Handle a dispatched request.
	 */
	protected function handle() {

		$logger = hwzi_get_logger();

		$logger->info( 'Start async handle method', static::class );

		// quit if post id is missing
		if(!isset($_POST['post_id']) || empty($_POST['post_id'])){

			$logger->error( 'missing post id', static::class );
			return;
		}

		$post_id = $_POST['post_id'];	

		if(!isset($_POST['fields']) ||empty($_POST['fields'])){
			
			$logger->error( 'define at least one field to update', static::class );
			return;
		}

		$logger->info( sprintf('Processing invoice %d', $post_id) , static::class );	
		$logger->info( sprintf('Received data: %s', json_encode($_POST)) , static::class );

		$fields = $_POST['fields'];

		$invoice = hw_get_invoice($post_id);
		$payment_id = hwzi_get_invoice_zoho_payment_id($post_id);
		
		$adapter = new Invoices($invoice);
		$data = $adapter->getFields($fields);

		$logger->info( sprintf('Payload data: %s', json_encode($data)) , static::class );

		try{

			Payments::updateRecord($payment_id, $data);
		}catch(SDKException $e){

			SDKLogger::err($e);
			$logger->error( 'SDK Error: ' . $e->getErrorMessage(), static::class );
		}

		$logger->info( 'Request dispatched successfully' , self::class );
	}

	public function directTest($post_id){

		OAuth::initialize();
		$logger = hwzi_get_logger();

		$logger->info( 'Test: Initialize direct test', static::class );

		// quit if post id is missing
		if(empty($post_id)){

			$logger->error( 'Test: missing post id', static::class );
			return;
		}

		$invoice = hw_get_invoice($post_id);
		
		$payment_id = hwzi_get_invoice_zoho_payment_id($post_id);
		$fields = ['hw_amount', 'hw_due_date', 'hw_invoice_status', 'hw_type', 'hw_paid_date'];

		$adapter = new Invoices($invoice);
		$data = $adapter->getFields($fields);

		$logger->info( sprintf('Test: Received data: %s', json_encode($_POST)) , static::class );
		
		try{

			Payments::updateRecord($payment_id, $data);

		}catch(SDKException $e){

			SDKLogger::err($e);
			$logger->error( 'Test SDK Error: ' . $e->getErrorMessage(), static::class );
		}

		$logger->info( 'Test: concluded successfully' , self::class );
	}
}