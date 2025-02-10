<?php

namespace VSC\API;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Yaml\Yaml;

class ViruscampAPI
{
    private $client;
    private $baseUri;
    private $authToken;

    public function __construct()
    {
        
        $config = Yaml::parseFile(__DIR__ . '/../../resources/config/viruscamp.yml');
        $this->baseUri = $config['api']['baseUri'];
        $this->authToken = $config['api']['authToken'];

        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'timeout' => 30.0
        ]);
    }

    /**
     * Effectuer une requête GET
     *
     * @param string $endpoint
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    private function get(string $endpoint, array $params = [])
    {
        return $this->request('GET', $endpoint, ['query' => $params]);
    }

    /**
     * Effectuer une requête POST
     *
     * @param string $endpoint
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    private function post(string $endpoint, array $data = [])
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    /**
     * Effectuer une requête PUT
     *
     * @param string $endpoint
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    private function put(string $endpoint, array $data = [])
    {
        return $this->request('PUT', $endpoint, ['json' => $data]);
    }

    /**
     * Effectuer une requête DELETE
     *
     * @param string $endpoint
     * @return mixed
     * @throws Exception
     */
    private function delete(string $endpoint)
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Méthode générique pour gérer les requêtes HTTP
     *
     * @param string $method
     * @param string $endpoint
     * @param array $options
     * @return mixed
     * @throws Exception
     */
    private function request(string $method, string $endpoint, array $options = [])
    {
        try {
            $options['headers'] = $this->authToken ? ['Authorization' => 'Bearer ' . $this->authToken] : [];
            $response = $this->client->request($method, $endpoint, $options);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            $this->handleRequestError($e);
        }
    }

    /**
     * Gérer les erreurs de requêtes
     *
     * @param RequestException $e
     * @throws Exception
     */
    private function handleRequestError(RequestException $e)
    {
        $errorMessage = $e->getMessage();
        if ($e->hasResponse()) {
            $response = $e->getResponse();
            $errorMessage = $response->getBody();
        }
        throw new Exception('Erreur lors de la requête: ' . $errorMessage);
    }

    /**
     * Authentifier un utilisateur avec email et mot de passe
     *
     * @param string $email
     * @param string $password
     * @return mixed
     * @throws Exception
     */
    public function login(string $email, string $password)
    {
        $data = [
            'email' => $email,
            'password' => $password,
        ];

        return $this->post('login', $data);
    }

    /**
     * Obtenir les informations d'un utilisateur connecté
     *
     * @return mixed
     * @throws Exception
     */
    public function getUserInfo()
    {
        return $this->get('me');
    }

    /**
     * Obtenir les informations d'un client par ID
     *
     * @param int $id
     * @return mixed
     * @throws Exception
     */
    public function getCustomerById(int $id)
    {
        return $this->get('customer/' . $id);
    }

    /**
     * Créer un nouveau client
     *
     * @param array $customerData
     * @return mixed
     * @throws Exception
     */
    public function createCustomer(array $customerData)
    {
        return $this->post('customer', $customerData);
    }

    /**
     * Mettre à jour un client existant
     *
     * @param int $id
     * @param array $customerData
     * @return mixed
     * @throws Exception
     */
    public function updateCustomer(int $id, array $customerData)
    {
        return $this->put('customer/' . $id, $customerData);
    }

    /**
     * Supprimer un client
     *
     * @param int $id
     * @return mixed
     * @throws Exception
     */
    public function deleteCustomer(int $id)
    {
        return $this->delete('customer/' . $id);
    }

    /**
     * Scanner un fichier
     *
     * @param string $filePath
     * @return mixed
     * @throws Exception
     */
    public function scanFile(string $filePath)
    {
        $data = [
            'file' => fopen($filePath, 'r')
        ];
        return $this->post('scan', $data);
    }

    public function hello(){
        return "hello word";
    }
}
