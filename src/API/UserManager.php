<?php
namespace VSC\API;
use Exception;
use RuntimeException;
use CURLFile;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use VSC\Config\VirusCampConfiguration;
use GuzzleHttp\Psr7\Utils;
/**
 * Classe pour gérer les utilisateurs et l'authentification
 */
class UserManager extends VirusCamp
{
    public function login(string $email, string $password)
    {
        return $this->post('login', [
            'email' => $email,
            'password' => $password,
        ]);
    }

    public function getUserInfo()
    {
        return $this->get('me');
    }

    public function getCustomerById(int $id)
    {
        return $this->get("customer/{$id}");
    }

    public function createCustomer(array $customerData)
    {
        return $this->post('customer', $customerData);
    }

    public function updateCustomer(int $id, array $customerData)
    {
        return $this->post("customer/{$id}", $customerData);
    }

    public function deleteCustomer(int $id)
    {
        return $this->post("customer/{$id}");
    }
}