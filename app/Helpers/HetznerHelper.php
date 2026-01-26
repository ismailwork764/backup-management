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
    /**
     * Get total storage usage (in bytes) for a subaccount directory on a specific Hetzner Storage Box.
     *
     * @param StorageServer $server
     * @param string $subAccountName Directory name for the subaccount
     * @return int Total size in bytes
     */
    public static function getSubAccountUsage(StorageServer $server, $subAccountName)
    {
        try {
            // Create SFTP connection using positional arguments
            $connectionProvider = new SftpConnectionProvider(
                $server->server_address,  // host
                $server->username,         // username
                $server->password,         // password
                null,                      // privateKey
                null,                      // passphrase
                22,                        // port
                false,                     // useAgent
                10,                        // timeout
                10,                        // maxTries
                null,                      // hostFingerprint
                null,                      // connectivityChecker
                []                         // preferredAlgorithms (must be array)
            );
            
            $adapter = new SftpAdapter($connectionProvider, '/');
          
            $filesystem = new Filesystem($adapter);
            
            Log::info("Listing contents of directory: {$subAccountName}");
            $files = $filesystem->listContents($subAccountName, true);
            
            // Convert iterator to array for debugging
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
