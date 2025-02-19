<?php

namespace VSC\API;

use Exception;
use RuntimeException;
use CURLFile;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use VSC\Config\VirusCampConfiguration;
use GuzzleHttp\Psr7\Utils;


class VirusCampAPI
{
    private  $client;
    private string $baseUri  = "";
    private string $authToken  = "";
    private string $username  = "";
    private string $password  = "";
    public array $headers = [
        "headers" => [
            "Accept" => 'application/json',
        ]
    ];
    private array $jwtTokenInfo = [];

    /**Begin File info */
    private array $fileInformation = [];
    private string $fileName = "";
    private string $fileExtension = "";
    private string $fileBaseName = "";
    private string $fileDirName = "";

    /**End File info */

    public function __construct()
    {
        $this->init();
    }

    /**Charge les configurations nécessaires pour */
    public function init(){
        VirusCampConfiguration::load();
        $this->baseUri = VirusCampConfiguration::getParameter('baseUri');
        $this->authToken = VirusCampConfiguration::getParameter('authToken');
        $this->username =  VirusCampConfiguration::getParameter('username');
        $this->password =  VirusCampConfiguration::getParameter('password');
        $this->client = new Client([
                'base_uri' =>$this->baseUri,
                'timeout' => 30.0
        ]);
        $this->generateNewJwtToken();
    }

    /*Génère un nouveau token Jwt qui sera utilisé dans les autres endpoint du site*/
    public function generateNewJwtToken(){
        $additional_headers = [
            "Content-Type"=>"application/x-www-form-urlencoded",
        ];
        $this->addAdditionalHeaders($additional_headers);
        $additional_data = [
            "form_params"=>[
                "username"=>$this->username,
                "password"=>$this->password,
            ]
        ];
        $this->jwtTokenInfo = $this->post('login',$additional_data);
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
        return $this->request('GET', $endpoint, $params);
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
        return $this->request('POST', $endpoint, $data);
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
            if(!empty($this->getGeneratedJwtToken())){
                $this->addAdditionalHeaders(["Cookie"=>"jwttoken={$this->getGeneratedJwtToken()}"]);
            }
            $options = array_merge($this->headers,$options);
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
        $this->loadFile($filePath);
        unset($this->headers['headers']['Content-Type']);
        $additional_data = [
            "multipart"=>[
                [
                    "name"=>"file",
                    "contents"=>Utils::tryFopen($this->getFilePath(),'r'),
                    
                ],
            ]
        ]; 
        return $this->post('scan', $additional_data);
    }

    /** Ajoute des valeurs supplémentaires dans le header 
     * @param array $additionalElement
     * @return void
     */
    public function addAdditionalHeaders(array $additionalElement): void{
        $this->headers['headers']= array_merge($this->headers['headers'],$additionalElement);
    }

    /** Recupère les éléments contenus dans le header 
     * @return array $headers
    */
    public function getHeaders(): array {
        return $this->headers;
    }

    /** Retourne le token généré par la méthode generateNewJwtToken
     * @return string
    */
    public function getGeneratedJwtToken(): string {
        return $this->jwtTokenInfo['token'] ?? "";
    }

    public function loadFile(string $filePath){
        if(!file_exists($filePath)){
            throw new RuntimeException("Le fichier n'existe pas {$filePath}");
        }

        $this->fileInformation = pathinfo($filePath);
        if(isset($this->fileInformation['filename'])){
            $this->fileName = $this->fileInformation['filename'];
        }
        if(isset($this->fileInformation['extension'])){
            $this->fileExtension = $this->fileInformation['extension'];
        }
        if(isset($this->fileInformation['basename'])){
            $this->fileBaseName = $this->fileInformation['basename'];
        }
        if(isset($this->fileInformation['dirname'])){
            $this->fileDirName = $this->fileInformation['dirname'];
        }
    }
    public function getFileInformation(){
        return $this->fileInformation;
    }

    public function getFileFileName(){
       if(empty($this->fileInformation) && !isset($this->fileInformation['filename'])){
            return null;
       }
       return $this->fileInformation['filename'];
    }

    public function getFileExtension(){
        return $this->fileExtension;
    }

    public function getFileBaseName(){
        return $this->fileBaseName;
    }

    public function getFilePath(){
        return $this->fileDirName.'/'.$this->fileBaseName;
    }



}
