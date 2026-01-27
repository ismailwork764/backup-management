<?php

namespace App\Helpers;


use League\Flysystem\Filesystem;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\FilesystemException;
use Illuminate\Support\Facades\Log;
use App\Models\StorageServer;

class HetznerHelper
{
    public static function getSubAccountUsage(StorageServer $server, $subAccountName)
    {
        try {
            $connectionProvider = new SftpConnectionProvider(
                $server->server_address,  
                $server->username,         
                $server->password,         
                null,                      
                null,                      
                22,                        
                false,                     
                10,                        
                10,                        
                null,                      
                null,                      
                []                         
            );
            
            $adapter = new SftpAdapter($connectionProvider, '/');
          
            $filesystem = new Filesystem($adapter);
            
            Log::info("Listing contents of directory: {$subAccountName}");
            $files = $filesystem->listContents($subAccountName, true);
            
            $filesArray = iterator_to_array($files);
            Log::info("Found " . count($filesArray) . " items in directory: {$subAccountName}");
       
            $totalSize = 0;
            $fileCount = 0;
            foreach ($filesArray as $file) {
                if (($file['type'] ?? null) === 'file' && isset($file['fileSize'])) {
                    $totalSize += $file['fileSize'];
                    $fileCount++;
                }
            }
           
            return $totalSize;
        } catch (FilesystemException $e) {
            Log::error("Hetzner SFTP Usage Check Failed: " . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            Log::error("Hetzner SFTP Connection Error: " . $e->getMessage());
            return null;
        }
    }
}
