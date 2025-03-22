<?php

namespace Saulmoralespa\Siigo;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Utils;

class Client
{
    const API_BASE_URL = "https://api.siigo.com/";
    const API_VERSION = "v1";
    const PARTNER_ID = "saulmoralespa";
    private static string $tokenFilePath = "token.json";

    public function __construct(
        private $username,
        private $accessKey
    ) {
    }

    public function client(): GuzzleClient
    {
        return new GuzzleClient([
            "base_uri" => self::API_BASE_URL
        ]);
    }

    /**
     * @throws \Exception|GuzzleException
     */
    public function getAccessToken(): string
    {
        if ($this->isTokenExpired()) {
            $options = [
                "json" => [
                    "username" => $this->username,
                    "access_key" => $this->accessKey
                ]
            ];
            $response = $this->makeRequest("POST", "auth", $options, true);
            $this->saveToken($response);
        }

        $data = json_decode(file_get_contents(self::$tokenFilePath), true);
        return $data['access_token'];
    }

    /**
     * @throws \Exception|GuzzleException
     */
    public function createProduct(array $data): array
    {
        return $this->makeRequest("POST", self::API_VERSION . "/products", [
            "json" => $data
        ]);
    }

    /**
     * @throws \Exception|GuzzleException
     */
    public function getProducts(array $queries = []): array
    {
        return $this->makeRequest("GET", self::API_VERSION . "/products", [
            "query" => $queries
        ]);
    }

    /**
     * @throws \Exception|GuzzleException
     */
    public function getProductById(string $id): array
    {
        return $this->makeRequest("GET", self::API_VERSION . "/products/$id");
    }

    /**
     * @throws GuzzleException
     */
    public function updateProduct(string $id, array $data): array
    {
        return $this->makeRequest("PUT", self::API_VERSION . "/products/$id", [
            "json" => $data
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function deleteProduct(string $id): array
    {
        return $this->makeRequest("DELETE", self::API_VERSION . "/products/$id");
    }

    /**
     * @throws GuzzleException
     */
    public function createClient(array $data): array
    {
        return $this->makeRequest("POST", self::API_VERSION . "/customers", [
            "json" => $data
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function getClients(array $queries): array
    {
        return $this->makeRequest("GET", self::API_VERSION . "/customers", [
            "query" => $queries
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function getClientById(string $id): array
    {
        return $this->makeRequest("GET", self::API_VERSION . "/customers/$id");
    }

    /**
     * @throws GuzzleException
     */
    public function updateClient(string $id, array $data): array
    {
        return $this->makeRequest("PUT", self::API_VERSION . "/customers/$id", [
            "json" => $data
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function createInvoice(array $data): array
    {
        return $this->makeRequest("POST", self::API_VERSION . "/invoices", [
            "json" => $data
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function updateInvoice(string $id, array $data): array
    {
        return $this->makeRequest("PUT", self::API_VERSION . "/invoices/$id", [
            "json" => $data
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function getInvoiceById(string $id): array
    {
        return $this->makeRequest("GET", self::API_VERSION . "/invoices/$id");
    }

    /**
     * @throws GuzzleException
     */
    public function deleteInvoice(string $id): array
    {
        return $this->makeRequest("DELETE", self::API_VERSION . "/invoices/$id");
    }

    /**
     * @throws GuzzleException
     */
    public function annulInvoice(string $id): array
    {
        return $this->makeRequest("POST", self::API_VERSION . "/invoices/$id/annul");
    }

    /**
     * @throws GuzzleException
     */
    public function sendInvoiceByEmail(string $id, array $data): array
    {
        return $this->makeRequest("POST", self::API_VERSION . "/invoices/$id/mail", [
            "json" => $data
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function getErrorsInvoiceReject(string $id): array
    {
        return $this->makeRequest("GET", self::API_VERSION . "/invoices/$id/stamp/errors");
    }

    /**
     * @throws GuzzleException
     */
    public function getPdfInvoice(string $id): array
    {
        return $this->makeRequest("GET", self::API_VERSION . "/invoices/$id/pdf");
    }

    /**
     * @throws GuzzleException
     */
    public function getAccountGroups(): array
    {
        return $this->makeRequest("GET", self::API_VERSION . "/account-groups");
    }

    /**
     * @throws GuzzleException
     */
    public function getTaxes(): array
    {
        return $this->makeRequest("GET", self::API_VERSION . "/taxes");
    }

    /**
     * @throws GuzzleException
     */
    public function getPriceLists(): array
    {
        return $this->makeRequest("GET", self::API_VERSION . "/price-lists");
    }

    /**
     * @throws GuzzleException
     */
    public function getWarehouses(): array
    {
        return $this->makeRequest("GET", self::API_VERSION . "/warehouses");
    }

    /**
     * @throws GuzzleException
     */
    public function getUsers(): array
    {
        return $this->makeRequest("GET", self::API_VERSION . "/users");
    }

    /**
     * @throws GuzzleException
     */
    public function getDocumentTypes(array $queries): array
    {
        return $this->makeRequest("GET", self::API_VERSION . "/document-types", [
            "query" => $queries
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function getPaymentsMethods(array $queries): array
    {
        return $this->makeRequest("GET", self::API_VERSION . "/payment-types", [
            "query" => $queries
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function getCostCenters(): array
    {
        return $this->makeRequest("GET", self::API_VERSION . "/cost-centers");
    }

    /**
     * @throws GuzzleException
     */
    public function getFixedAssets(): array
    {
        return $this->makeRequest("GET", self::API_VERSION . "/fixed-assets");
    }

    /**
     * @throws GuzzleException
     */
    public function subscribeWebhook(array $data): array
    {
        return $this->makeRequest("POST", self::API_VERSION . "/webhooks", [
            "json" => $data
        ]);
    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    private function makeRequest(string $method, string $uri, array $options = [], bool $auth = false): array
    {
        try {
            if (!$auth) {
                $options["headers"] = [
                    "Authorization" => "Bearer " . $this->getAccessToken(),
                    "Partner-Id" => self::PARTNER_ID,
                    "Content-Type" => "application/json"
                ];
            }

            $res = $this->client()->request($method, $uri, $options);
            $content =  $res->getBody()->getContents();
            return self::responseArray($content);
        } catch (RequestException $exception) {
            $content = $exception->getResponse()->getBody()->getContents();
            $response = self::responseArray($content);
            $errorMessage = $this->handleErrors($response) ?? $exception->getMessage();
            throw new \Exception($errorMessage);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    public static function responseArray(string $content): array
    {
        return Utils::jsonDecode($content, true);
    }

    public function handleErrors(array $response): ?string
    {
        if ((array_key_exists('Errors', $response) &&
                is_array($response['Errors'])) ||
            (array_key_exists('errors', $response) &&
                is_array($response['errors']))
        ) {
            $errors = $response['Errors'] ?? $response['errors'];

            $arr = array_map(function ($error) {
                return $error['Message'] ?? $error['message'];
            }, $errors);

            return implode(PHP_EOL, $arr);
        }

        return null;
    }

    /**
     * @throws \Exception
     */
    private function isTokenExpired(): bool
    {
        if (!file_exists(self::$tokenFilePath)) {
            return true;
        }
        $tokenData = json_decode(file_get_contents(self::$tokenFilePath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new  \Exception("Failed to decode token data: " . json_last_error_msg());
        }
        return $tokenData['expires_at'] <= time();
    }

    /**
     * @throws \Exception
     */
    private static function saveToken(array $accessToken): void
    {
        $accessToken['expires_at'] = time() + 86400; //24 hours
        if (file_put_contents(self::$tokenFilePath, json_encode($accessToken)) === false) {
            throw new \Exception("Failed to write token data to file.");
        }
    }

    public function setTokenFilePath(string $tokenFilePath): static
    {
        self::$tokenFilePath = $tokenFilePath;
        return $this;
    }
}