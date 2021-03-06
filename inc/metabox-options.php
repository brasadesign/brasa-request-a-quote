<?php
/**
 * Metaboxes options
 */

$metabox_brasa_request_a_quote = new Brasa_Request_A_Quote_Odin_Metabox(
    'metabox_brasa_request_a_quote',
    __( 'Brasa Request a Quote', 'brasa-request-quote'),
    'product',
    'normal', // Contexto (opções: normal, advanced, ou side) (opcional)
    'high'
);
$metabox_brasa_request_a_quote->set_fields(
    array(
    	array(
    		'id'          => 'is_request_a_quote',
    		'label'       => __( 'Product for quotation only', 'brasa-request-quote' ), // Obrigatório
    		'type'        => 'radio',
		    'default'     => 'false',
    		'description' => '',
    		'options'     => array(
        		'true'   => __( 'Yes', 'brasa-request-quote' ),
        		'false'   => __( 'No', 'brasa-request-quote' ),
    		),
    	)
    )
);
