<?php

namespace VSC\Config;

use Exception;
use Symfony\Component\Yaml\Yaml;


class VirusCampConfiguration {
    private static string $configPath = __DIR__ . '/../../resources/config/viruscamp.yml';
    private static array $configParameters = [];
    private static string $principalKey = 'api';

    public static function load ($configPath=null) :void {
        try{
            if(!empty($configPath)){
                self::$configPath = $configPath;
            }
            self::$configParameters = Yaml::parseFile(self::$configPath);

        }catch( Throwable $e){
            throw new Exception('Erreur lors du chargement du fichier de configuration: ' . $e->getMessage());
        }

    }

    public static function setPrincipalKey(string $newPrincipalKey): void{
        if(empty($newPrincipalKey)){
            throw new Exception("La clé de configuration ne peut pas être vide. Veuillez spécifier une clé valide.");
        }
        self::$principalKey = $newPrincipalKey;
    }

    public static function getParameter($parametersKey = null): string {
        if(empty($parametersKey)){
            throw new Exception("Le paramètre demandé ne peut pas être vide");
        }

        if(!isset(self::$configParameters[self::$principalKey][$parametersKey])){
            throw new Exception("La clé demandé n'existe pas dans la configuration");
        }
        if(empty(self::$configParameters[self::$principalKey][$parametersKey])){
            throw new Exception("La clé {$parametersKey} devrait contenir au moins une valeur ");
        }
        return self::$configParameters[self::$principalKey][$parametersKey];
    }


}