<?php

namespace Saulmoralespa\Siigo\Tests;

use Saulmoralespa\Siigo\Client;
use PHPUnit\Framework\TestCase;
use Dotenv\Dotenv;

class SiigoTest extends TestCase
{
    public Client $siigo;

    protected function setUp(): void
    {
        $dotenv = Dotenv::createMutable(__DIR__ . '/../');
        $dotenv->load();

        $username = $_ENV['USERNAME'];
        $accessKey = $_ENV['ACCESS_KEY'];

        $this->siigo = new Client($username, $accessKey);
    }

    public function testToken()
    {
        $response = $this->siigo->getAccessToken();
        $this->assertIsString($response);
        $jwt_parts = explode('.', $response);
        $this->assertCount(3, $jwt_parts);
    }

    public function testCreateProduct()
    {
        $sku = 'code-7SD';

        $data = [
            'code' => $sku,
            'name' => 'Producto de prueba',
            'account_group' => 121,
            'taxes' => [
                [
                    'id' => 18534,
                    'name' => 'Bebidas azucaradas',
                    'type' => 'Bebidas azucaradas',
                    'milliliters' => '1000',
                    'rate' => '35'
                ]
            ],
            'prices' => [
                [
                    'currency_code' => 'COP',
                    'price_list' => [
                        [
                            'position' => 1,
                            'value' => 12000
                        ]
                    ]
                ]
            ],
            'unit' => 'INH',
            'unit_label' => 'unidad',
            'reference' => 'REF1',
            'description' => 'Camiseta de algodón blanca',
            'additional_fields' =>  [
                'barcode' => 'B0123',
                'brand' => 'Gef',
                'tariff' => '151612',
                'model' => 'Loiry'
            ]
        ];
        $response = $this->siigo->createProduct($data);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('code', $response);
        $this->assertEquals($sku, $response['code']);
    }

    public function testGetProducts()
    {
        $queries = [
            "code" => "code-4SD",
            "created_start" => "2024-05-25",
            /*"created_end" => "2024-05-25",
            "updated_start" => "2024-05-25",
            "updated_end" => "2024-05-25",*/
            "id" => "63f16b9d-241d-4fb8-8bb3-c71441d806bd"
        ];

        $response = $this->siigo->getProducts($queries);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('results', $response);
    }

    public function testGetProductById()
    {
        $productId = "63f16b9d-241d-4fb8-8bb3-c71441d806bd";
        $response = $this->siigo->getProductById($productId);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
    }

    public function testCreateClient()
    {
        $data = [
            "type" => "Customer", //Customer, Supplier, Other
            "person_type" => "Person", //Company, Person
            "id_type" => "31", //13 Cédula, 31 NIT
            "identification" => '123456768',
            "check_digit" => "4", //optional
            "name" => [
                "Isabel",
                "García"
            ],
            "comercial_name" => "Isabel García",
            "branch_office" => 0,
            "active" => true, //optional
            "vat_responsible" => false, //optional, default false
            "fiscal_responsibilities" => [
                [
                    "code" => "R-99-PN", //R-99-PN No Aplica - Otros, 0-13, 0-15, 0-23, 0-47
                ]
            ],
            "address" => [
                "address" => "Cra. 18 #79A - 42",
                "city" => [
                    "country_code" => "CO",
                    "state_code" => "11", //Bogotá
                    "city_code" => "11001" //Bogotá
                ],
                "postal_code" => "11001",  //optional
            ],
            "phones" => [
                [
                    "indicative" => "57", //optional
                    "number" => "3001234567", //optional
                    "extension" => "123" //optional
                ]
            ],
            "contacts" => [
                [
                    "first_name" => "Marcos",
                    "last_name" => "Pérez", //optional
                    "email" => "marcos.castillo@contacto.com", //optional
                    "phone" => [
                        "indicative" => "57", //optional
                        "number" => "3001234567", //optional
                        "extension" => "123" //optional
                    ]
                ]
            ],
            "comments" => "Comentarios del cliente", //optional
            "seller" => 1232, //optional
            "collector_id" => 1232, //optional

        ];
        $response = $this->siigo->createClient($data);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
    }

    public function testGetClients()
    {
        $queries = [
            "identification" => "123456766",
            "branch_office" => 0,
            "created_start" => "2024-05-25",
            /*"created_end" => "2024-05-25",
            "updated_start" => "2024-05-25",
            "updated_end" => "2024-05-25",*/
        ];

        $response = $this->siigo->getClients($queries);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('results', $response);
    }

    public function testUpdateClient()
    {
        $clientId = "7f3cacf7-3a4d-4713-aafb-5a5407b07647";
        $data = [
            "type" => "Customer", //Customer, Supplier, Other
            "person_type" => "Person", //Company, Person
            "id_type" => "31", //13 Cédula, 31 NIT
            "identification" => '123456766',
            "check_digit" => "4", //optional
            "name" => [
                "Andres",
                "Pérez"
            ],
            "comercial_name" => "Andres Pérez",
            "branch_office" => 0,
            "active" => true, //optional
            "vat_responsible" => false, //optional, default false
            "fiscal_responsibilities" => [
                [
                    "code" => "R-99-PN", //R-99-PN No Aplica - Otros, 0-13, 0-15, 0-23, 0-47
                ]
            ],
            "address" => [
                "address" => "Cra. 18 #79A - 42",
                "city" => [
                    "country_code" => "CO",
                    "state_code" => "11", //Bogotá
                    "city_code" => "11001" //Bogotá
                ],
                "postal_code" => "11001",  //optional
            ],
            "phones" => [
                [
                    "indicative" => "57", //optional
                    "number" => "3001234567", //optional
                    "extension" => "123" //optional
                ]
            ],
            "contacts" => [
                [
                    "first_name" => "Andres",
                    "last_name" => "Pérez", //optional
                    "email" => "marcos.castillo@contacto.com", //optional,
                    "phone" => [
                        "indicative" => "57", //optional
                        "number" => "3001234567", //optional
                        "extension" => "123" //optional
                    ]
                ]
            ],
            "comments" => "Comentarios del cliente", //optional
            "seller" => 1232, //optional
            "collector_id" => 1232, //optional
        ];

        $response = $this->siigo->updateClient($clientId, $data);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
    }

    public function testGetClientById()
    {
        $clientId = "7f3cacf7-3a4d-4713-aafb-5a5407b07647";
        $response = $this->siigo->getClientById($clientId);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
    }

    public function testCreateInvoice()
    {
        $data = [
            "document" => [
                "id" => 2372 // use getDocumentTypes to get the id
            ],
            "date" => "2024-06-01", //current date
            //"number" => 123456, //optional
            "customer" => [
                "identification" => "123456766", // Should be active
                "branch_office" => 0 //optional
            ],
            "seller" => 62,
            "stamp" => [
                "send" => false, //optional true send the invoice to the DIAN else default false
            ],
            "mail" => [
                "send" => false //optional true send the invoice to the customer else default false
            ],
            "observations" => "Observaciones de la factura", //optional
            /*"retentions" => [
                [
                    "id" => 1280
                ]
            ],*/ //optional
            //"advance_payment" => 12 //optional
            //"cost_center" => 235, //optional should be active
            /*"currency" => [
                "code" => "COP",
                "exchange_rate" => 1 //optional
            ],*/
            "items" => [
                [
                    "code" => "code-7SD",
                    "description" => "Producto de prueba",
                    "quantity" => 1,
                   /* "price" => 12000,
                    "discount" => 0, //optional
                    "seller" => 62,
                    "warehouse" => 1232, //optional*/
                    "taxes" => [
                        [
                            "id" => 1270, //option
                        ]
                    ],
                    "taxed_price" => 1000,
                ]
            ],
            "adittional_fields" => [

            ], //optional
            "payments" => [
                [
                    "id" => 542, // Should exist
                    "value" => 1000,
                    "due_date" => "2024-05-25"
                ]
            ],
            /*"global_discounts" => [
                [
                    "id" => 13156, //Should exist
                    "percentage" => 10, //optional
                    "value" => 100 //optional
                ]
            ]*/
        ];
        $response = $this->siigo->createInvoice($data);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
    }

    public function testUpdateInvoice()
    {
        $data = [
            "document" => [
                "id" => 2372 // use getDocumentTypes to get the id
            ],
            "date" => "2024-06-03", //current date
            //"number" => 123456, //optional
            "customer" => [
                "identification" => "123456766", // Should be active
                "branch_office" => 0 //optional
            ],
            "seller" => 62,
            "observations" => "Observaciones de la factura", //optional
            /*"retentions" => [
                [
                    "id" => 1280
                ]
            ],*/ //optional
            //"advance_payment" => 12 //optional
            /*"cost_center" => 235, //optional should be active
            "currency" => [
                "code" => "COP",
                "exchange_rate" => 1 //optional
            ],*/
            "items" => [
                [
                    "code" => "code-7SD",
                    "description" => "Producto de prueba",
                    "quantity" => 1,
                    "price" => 12000,
                    /*"discount" => 0, //optional
                    "seller" => 62,
                    "warehouse" => 1232, //optional*/
                ]
            ],
            "adittional_fields" => [

            ], //optional
            "payments" => [
                [
                    "id" => 542, // Should exist
                    "value" => 12000,
                    "due_date" => "2024-06-03"
                ]
            ]
        ];
        $id = "a7f62ea6-42f3-4b16-a48e-8bbe0ed06564";
        $response = $this->siigo->updateInvoice($id, $data);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
    }

    public function testGetDocumentTypes()
    {

        $queries = [
            "type" => "FV", //FV Factura de venta, NC Nota crédito, RC Recibo de caja,
            "id" => 26562
        ];
        $response = $this->siigo->getDocumentTypes($queries);
        $this->assertIsArray($response);
    }

    public function testGetInvoiceById()
    {
        $id = "a7f62ea6-42f3-4b16-a48e-8bbe0ed06564";
        $response = $this->siigo->getInvoiceById($id);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
    }

    public function testDeleteInvoice()
    {
        $id = "0f9d4b2a-dab8-4bdc-bfe1-9574d35e2cee";
        $response = $this->siigo->deleteInvoice($id);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('deleted', $response);
        $this->assertTrue($response['deleted']);
    }

    public function testAnnulInvoice()
    {
        $id = "85c8636a-684f-4f6e-9f52-27bc09af979a";
        $response = $this->siigo->annulInvoice($id);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
    }

    public function testSendInvoiceByEmail()
    {
        $id = "85c8636a-684f-4f6e-9f52-27bc09af979a";

        $data = [
            "mail_to" => "cabr110042@siigo.co",
            "copy_to" => "juan.casallas@siigo.com",
        ];

        $response = $this->siigo->sendInvoiceByEmail($id, $data);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('observations', $response);
    }

    public function testGetErrorsInvoiceReject()
    {
        $id = "a7f62ea6-42f3-4b16-a48e-8bbe0ed06564";
        $response = $this->siigo->getErrorsInvoiceReject($id);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('errors', $response);
    }

    public function testGetPdfInvoice()
    {
        $id = "a7f62ea6-42f3-4b16-a48e-8bbe0ed06564";
        $response = $this->siigo->getPdfInvoice($id);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('base64', $response);
    }

    public function testGetUsers()
    {
        $response = $this->siigo->getUsers();
        $this->assertIsArray($response);
        $this->assertArrayHasKey('results', $response);
        var_dump($response);
    }

    public function testPaymentsMethods()
    {
        $queries = [
            "document_type" => "FV",

        ];
        $response = $this->siigo->getPaymentsMethods($queries);
        $this->assertIsArray($response);
    }

    public function testGetCostCenters()
    {
        $response = $this->siigo->getCostCenters();
        $this->assertIsArray($response);
    }

    public function testGetTaxes()
    {
        $response = $this->siigo->getTaxes();
        $this->assertIsArray($response);
    }
}
