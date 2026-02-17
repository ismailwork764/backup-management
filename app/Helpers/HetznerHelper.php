<?php

namespace App\Helpers;


use League\Flysystem\Filesystem;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
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
        $connectionProvider = new SftpConnectionProvider(
            $server->server_address, // host
            $server->username,       // username
            $server->password,       // password
            null,                    // privateKey
            22,                      // port
            false,                   // useAgent (must be bool)
            null,                    // passphrase
            10,                      // timeout
            10,                      // maxTries
            null,                    // hostFingerprint
            null,                    // connectivityChecker
            null                     // proxy
        );
        $adapter = new SftpAdapter($connectionProvider, '/');
        $filesystem = new Filesystem($adapter);
        $files = $filesystem->listContents($subAccountName, true);
        $totalSize = 0;
        foreach ($files as $file) {
            if (($file['type'] ?? null) === 'file' && isset($file['size'])) {
                $totalSize += $file['size'];
            }
        }
        return $totalSize;
    }
}
