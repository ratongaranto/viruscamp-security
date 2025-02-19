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
 * Classe pour gérer les fichiers et leurs scans
 */
class FileHandler extends VirusCamp
{
    private array $fileInformation = [];

    /**
     * Scanner un fichier (chemin du fichier)
     *
     * @param string $filePath
     * @return mixed
     * @throws Exception
     */
    public function scanFile(string $filePath)
    {

        unset($this->headers['headers']['Content-Type']);
        $additional_data = [
            "multipart"=>[
                [
                    "name"=>"file",
                    "contents"=>Utils::tryFopen($filePath,'r'),
                    'filename'=>$this->getNameFile($filePath)
                ],
            ]
        ]; 
        return $this->post('scan', $additional_data);
    }

    /**
     * Scanner un fichier
     *
     * @param string $filePath
     * @return mixed
     * @throws Exception
     */
    public function scanFileInInput(Array $file)
    {
        $this->loadFile($file["tmp_name"]);
        unset($this->headers['headers']['Content-Type']);
        $additional_data = [
            "multipart"=>[
                [
                    "name"=>"file",
                    "contents"=>Utils::tryFopen($file["tmp_name"],'r'),
                    'filename'=>$file["name"]
                    
                ],
            ]
        ]; 
        return ($this->post('scan', $additional_data));
    }


    public function resultsScan(Array|string $file,string $typeFile = "urlOrDir") : Array
    {
        if ($typeFile === "input") {
            extract($this->scanFileInInput($file));
        }elseif ($typeFile === "urlOrDir") {
            extract($this->scanFile($file));
        }
        $errorInScan = [];
        $infosScan = $this->get("file/".$uuid."/scans");
        foreach ($infosScan as $infos) {
            if ($infos['Detected']) {
                $errorInScan[]= [
                    'virusName' => $infos['VirusName'],
                    'antivirusName' => $infos['AntivirusEngineVersion']['AntivirusEngine']['Name'],
                ];
            }
        }
        return $errorInScan;
    }

    private function getNameFile($fileUrl){
        $path = parse_url($fileUrl, PHP_URL_PATH);
        $filename = basename($path);
        return $filename;
    }



    public function getScanResults(string $uuid): array
    {
        return $this->get("file/{$uuid}/scans");
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

    private function getFileInformation(){
        return $this->fileInformation;
    }

    private function getFileFileName(){
       if(empty($this->fileInformation) && !isset($this->fileInformation['filename'])){
            return null;
       }
       return $this->fileInformation['filename'];
    }

    private function getFileExtension(){
        return $this->fileExtension;
    }

    private function getFileBaseName(){
        return $this->fileBaseName;
    }

    private function getFilePath(){
        return $this->fileDirName.'/'.$this->fileBaseName;
    }
}