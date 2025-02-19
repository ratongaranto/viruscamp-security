<?php

namespace VSC\API;

use Exception;
use RuntimeException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use VSC\Config\VirusCampConfiguration;
use GuzzleHttp\Psr7\Utils;

/**
 * Classe principale pour gérer les appels API à VirusCamp
 */
class virusCamp
{
    protected Client $client;
    protected string $baseUri;
    protected string $authToken;
    protected array $headers;
    protected array $jwtTokenInfo = [];

    public function __construct()
    {
        $this->init();
    }

    /** Initialisation des paramètres et de l'authentification */
    protected function init()
    {
        VirusCampConfiguration::load();
        $this->baseUri = VirusCampConfiguration::getParameter('baseUri');
        $this->authToken = VirusCampConfiguration::getParameter('authToken');
        
        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'timeout' => 30.0,
        ]);

        $this->headers = [
            "headers" => ["Accept" => 'application/json']
        ];
        
        $this->generateNewJwtToken();
    }

    /** Génère un nouveau token JWT */
    protected function generateNewJwtToken()
    {
        $response = $this->post('login', [
            "form_params" => [
                "username" => VirusCampConfiguration::getParameter('username'),
                "password" => VirusCampConfiguration::getParameter('password'),
            ]
        ]);
        $this->jwtTokenInfo = $response;
    }

    /** Effectue une requête API générique */
    protected function request(string $method, string $endpoint, array $options = [])
    {
        try {
            if (!empty($this->getGeneratedJwtToken())) {
                $this->addAdditionalHeaders(["Cookie" => "jwttoken={$this->getGeneratedJwtToken()}"]);
            }
            $options = array_merge($this->headers, $options);
            $response = $this->client->request($method, $endpoint, $options);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            throw new Exception('Erreur lors de la requête: ' . $e->getMessage());
        }
    }

    public function get(string $endpoint, array $params = [])
    {
        return $this->request('GET', $endpoint, $params);
    }

    public function post(string $endpoint, array $data = [])
    {
        return $this->request('POST', $endpoint, $data);
    }

    public function addAdditionalHeaders(array $additionalElement): void
    {
        $this->headers['headers'] = array_merge($this->headers['headers'], $additionalElement);
    }

    public function getGeneratedJwtToken(): string
    {
        return $this->jwtTokenInfo['token'] ?? "";
    }
}