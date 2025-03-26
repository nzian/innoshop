<?php
/**
 * Copyright (c) Since 2024 InnoShop - All Rights Reserved
 *
 * @link       https://www.innoshop.com
 * @author     InnoShop <team@innoshop.com>
 * @license    https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

namespace InnoShop\RestAPI\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileManagerService implements FileManagerInterface
{
    protected string $fileBasePath = '';

    protected string $basePath = '';

    public function __construct()
    {
        $this->fileBasePath = public_path('catalog').$this->basePath;
    }

    /**
     * Retrieves directories within a base folder.
     *
     * @param  string  $baseFolder
     * @return array
     */
    public function getDirectories(string $baseFolder = '/'): array
    {
        $currentBasePath = rtrim($this->fileBasePath.$baseFolder, '/');
        $directories     = glob("$currentBasePath/*", GLOB_ONLYDIR);

        $result = [];
        foreach ($directories as $directory) {
            $baseName = basename($directory);
            $dirName  = str_replace($this->fileBasePath, '', $directory);
            if (is_dir($directory)) {
                $item           = $this->handleFolder($dirName, $baseName);
                $subDirectories = $this->getDirectories($dirName);
                if (! empty($subDirectories)) {
                    $item['children'] = $subDirectories;
                }
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Get files list
     * @throws Exception
     */
    public function getFiles(string $baseFolder, string $keyword = '', string $sort = 'created', string $order = 'desc', int $page = 1, int $perPage = 20): array
    {
        $currentBasePath = rtrim($this->fileBasePath.$baseFolder, '/');

        $directories = glob("$currentBasePath/*", GLOB_ONLYDIR);
        $folders     = [];
        foreach ($directories as $directory) {
            $baseName  = basename($directory);
            $dirPath   = str_replace($this->fileBasePath, '', $directory);
            $folders[] = [
                'id'           => $dirPath,
                'name'         => $baseName,
                'path'         => $dirPath,
                'is_dir'       => true,
                'thumb'        => asset('icon/folder.png'),
                'url'          => '',
                'mime'         => 'directory',
                'created_time' => filemtime($directory),
            ];
        }

        $files  = glob($currentBasePath.'/*');
        $images = [];
        foreach ($files as $file) {
            if (! is_file($file)) {
                continue;
            }
            $baseName = basename($file);
            if ($baseName === 'index.html' || ($keyword && ! str_contains($baseName, $keyword))) {
                continue;
            }
            $fileName                 = str_replace($this->fileBasePath, '', $file);
            $fileInfo                 = $this->handleImage($fileName, $baseName);
            $fileInfo['created_time'] = filemtime($file);
            $images[]                 = $fileInfo;
        }

        $allItems = array_merge($folders, $images);

        if ($sort === 'created') {
            usort($allItems, function ($a, $b) use ($order) {
                $timeA = $a['created_time'] ?? 0;
                $timeB = $b['created_time'] ?? 0;

                return ($order === 'desc') ? $timeB - $timeA : $timeA - $timeB;
            });
        } else {
            // folder always in front of files
            usort($allItems, function ($a, $b) use ($order) {
                if (($a['is_dir'] ?? false) && ! ($b['is_dir'] ?? false)) {
                    return -1;
                }
                if (! ($a['is_dir'] ?? false) && ($b['is_dir'] ?? false)) {
                    return 1;
                }

                return ($order === 'desc') ?
                    strcasecmp($b['name'], $a['name']) :
                    strcasecmp($a['name'], $b['name']);
            });
        }

        $allItems = array_map(function ($item) {
            unset($item['created_time']);

            return $item;
        }, $allItems);

        $collection   = collect($allItems);
        $currentItems = $collection->forPage($page, $perPage);

        return [
            'images'      => $currentItems->values(),
            'image_total' => $collection->count(),
            'image_page'  => $page,
        ];
    }

    /**
     * Creates a new directory.
     *
     * @param  string  $path
     * @return bool
     * @throws Exception
     */
    public function createDirectory(string $path): bool
    {
        try {
            $folderPath = public_path("catalog{$this->basePath}/{$path}");
            if (is_dir($folderPath)) {
                throw new Exception(trans('panel/file_manager.directory_already_exist'));
            }

            create_directories("catalog{$this->basePath}/{$path}");

            return true;
        } catch (Exception $e) {
            \Log::error('Create directory failed:', [
                'path'  => $path,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Moves a directory to a new path.
     *
     * @param  string  $sourcePath
     * @param  string  $destPath
     * @return bool
     * @throws Exception
     */
    public function moveDirectory(string $sourcePath, string $destPath): bool
    {
        try {
            if (empty($sourcePath) || empty($destPath)) {
                throw new Exception(trans('panel/file_manager.empty_path'));
            }

            $sourceDirPath = public_path("catalog/{$sourcePath}");
            $destDirPath   = public_path("catalog/{$destPath}");
            $folderName    = basename($sourcePath);
            $destFullPath  = rtrim($destDirPath, '/').'/'.$folderName;

            // confirm origin folder
            if (! is_dir($sourceDirPath)) {
                throw new Exception(trans('panel/file_manager.source_dir_not_exist'));
            }

            // confirm target folder
            if (! is_dir($destDirPath)) {
                throw new Exception(trans('panel/file_manager.target_dir_not_exist'));
            }

            if (is_dir($destFullPath)) {
                throw new Exception(trans('panel/file_manager.target_dir_exist'));
            }

            if (strpos($destPath, $sourcePath.'/') === 0) {
                throw new Exception(trans('panel/file_manager.cannot_move_to_subdirectory'));
            }

            \Log::info('Moving directory:', [
                'from' => $sourceDirPath,
                'to'   => $destFullPath,
            ]);

            if (! @rename($sourceDirPath, $destFullPath)) {
                \Log::error('Failed to move directory:', [
                    'error' => error_get_last(),
                ]);
                throw new Exception(trans('panel/file_manager.move_failed'));
            }

            return true;
        } catch (Exception $e) {
            \Log::error('Move directory failed:', [
                'error'       => $e->getMessage(),
                'source'      => $sourcePath,
                'destination' => $destPath,
            ]);
            throw $e;
        }
    }

    /**
     * Moves multiple files to a new directory.
     *
     * @param  array  $files
     * @param  string  $destPath
     * @return bool
     * @throws Exception
     */
    public function moveFiles(array $files, string $destPath): bool
    {
        try {
            if (empty($files)) {
                throw new Exception(trans('panel/file_manager.no_files_selected'));
            }

            $destFullPath = public_path("catalog/{$destPath}");
            if (! is_dir($destFullPath)) {
                throw new Exception(trans('panel/file_manager.target_dir_not_exist'));
            }

            foreach ($files as $fileName) {
                $sourcePath   = public_path("catalog/{$fileName}");
                $destFilePath = rtrim($destFullPath, '/').'/'.basename($fileName);

                \Log::info('Moving file:', [
                    'source'      => $sourcePath,
                    'destination' => $destFilePath,
                    'fileName'    => $fileName,
                    'destPath'    => $destPath,
                ]);

                if (file_exists($sourcePath)) {
                    if (file_exists($destFilePath)) {
                        @unlink($destFilePath);
                    }

                    if (! @rename($sourcePath, $destFilePath)) {
                        \Log::error('Failed to move file:', [
                            'source'      => $sourcePath,
                            'destination' => $destFilePath,
                            'error'       => error_get_last(),
                        ]);
                        throw new Exception(trans('panel/file_manager.move_failed'));
                    } else {
                        \Log::info('File moved successfully:', [
                            'from' => $sourcePath,
                            'to'   => $destFilePath,
                        ]);
                    }
                } else {
                    \Log::warning('Source file not found:', ['path' => $sourcePath]);
                    throw new Exception(trans('panel/file_manager.source_file_not_exist'));
                }
            }

            return true;
        } catch (Exception $e) {
            \Log::error('Move files failed:', [
                'error'       => $e->getMessage(),
                'files'       => $files,
                'destination' => $destPath,
            ]);
            throw $e;
        }
    }

    /**
     * Zips a folder and returns the zip path.
     *
     * @param  string  $imagePath
     * @return string
     */
    public function zipFolder(string $imagePath): string
    {
        $realPath = $this->fileBasePath.$imagePath;
        $zipName  = basename($realPath).'-'.date('Ymd').'.zip';
        $zipPath  = public_path($zipName);
        zip_folder($realPath, $zipPath);

        return $zipPath;
    }

    /**
     * Deletes a file or folder.
     *
     * @param  string  $path
     * @return bool
     * @throws Exception
     */
    public function deleteDirectoryOrFile(string $path): bool
    {
        try {
            $fullPath = public_path("catalog{$this->basePath}/{$path}");

            \Log::info('Deleting path:', [
                'path'   => $fullPath,
                'is_dir' => is_dir($fullPath),
            ]);

            if (is_dir($fullPath)) {
                // 检查目录是否为空
                $files = glob($fullPath.'/*');
                if ($files) {
                    throw new Exception(trans('panel/file_manager.directory_not_empty'));
                }

                // 删除目录
                if (! @rmdir($fullPath)) {
                    \Log::error('Failed to delete directory:', [
                        'path'  => $fullPath,
                        'error' => error_get_last(),
                    ]);
                    throw new Exception(trans('panel/file_manager.delete_failed'));
                }
            } elseif (file_exists($fullPath)) {
                // 删除文件
                if (! @unlink($fullPath)) {
                    \Log::error('Failed to delete file:', [
                        'path'  => $fullPath,
                        'error' => error_get_last(),
                    ]);
                    throw new Exception(trans('panel/file_manager.delete_failed'));
                }
            } else {
                \Log::warning('Path not found:', [
                    'path' => $fullPath,
                ]);
                throw new Exception(trans('panel/file_manager.file_not_exist'));
            }

            return true;
        } catch (Exception $e) {
            \Log::error('Delete path failed:', [
                'error' => $e->getMessage(),
                'path'  => $path,
            ]);
            throw $e;
        }
    }

    /**
     * Delete multiple files.
     *
     * @param  string  $basePath
     * @param  array  $files
     * @return bool
     * @throws Exception
     */
    public function deleteFiles(string $basePath, array $files): bool
    {
        try {
            if (empty($files)) {
                throw new Exception(trans('panel/file_manager.no_files_selected'));
            }

            foreach ($files as $file) {
                $filePath = public_path("catalog/{$basePath}/{$file}");

                \Log::info('Deleting file:', [
                    'path' => $filePath,
                ]);

                if (file_exists($filePath)) {
                    if (! @unlink($filePath)) {
                        \Log::error('Failed to delete file:', [
                            'path'  => $filePath,
                            'error' => error_get_last(),
                        ]);
                        throw new Exception(trans('panel/file_manager.delete_failed'));
                    }
                } else {
                    \Log::warning('File not found:', [
                        'path' => $filePath,
                    ]);
                }
            }

            return true;
        } catch (Exception $e) {
            \Log::error('Delete files failed:', [
                'error' => $e->getMessage(),
                'files' => $files,
            ]);
            throw $e;
        }
    }

    /**
     * Renames a file or folder.
     *
     * @param  string  $originPath
     * @param  string  $newPath
     * @return bool
     * @throws Exception
     */
    public function updateName(string $originPath, string $newPath): bool
    {
        try {
            $originFullPath = public_path("catalog{$this->basePath}{$originPath}");
            $newFullPath    = public_path("catalog{$this->basePath}{$newPath}");

            if (! is_dir($originFullPath) && ! file_exists($originFullPath)) {
                throw new Exception(trans('panel/file_manager.target_not_exist'));
            }

            if (file_exists($newFullPath)) {
                $dirPath     = dirname($newPath);
                $newName     = $this->getUniqueFileName($dirPath, basename($newPath));
                $newPath     = $dirPath === '/' ? "/{$newName}" : "{$dirPath}/{$newName}";
                $newFullPath = public_path("catalog{$this->basePath}{$newPath}");
            }

            if (! @rename($originFullPath, $newFullPath)) {
                \Log::error('Failed to rename:', [
                    'from'  => $originFullPath,
                    'to'    => $newFullPath,
                    'error' => error_get_last(),
                ]);
                throw new Exception(trans('panel/file_manager.rename_failed'));
            }

            return true;
        } catch (Exception $e) {
            \Log::error('Rename failed:', [
                'error'       => $e->getMessage(),
                'origin_path' => $originPath,
                'new_path'    => $newPath,
            ]);
            throw $e;
        }
    }

    /**
     * Uploads a file to a specified path.
     *
     * @param  UploadedFile  $file
     * @param  string  $savePath
     * @param  string  $originName
     * @return string
     */
    public function uploadFile(UploadedFile $file, string $savePath, string $originName): string
    {
        $originName = $this->getUniqueFileName($savePath, $originName);
        $filePath   = $file->storeAs($this->basePath.$savePath, $originName, 'catalog');

        return asset('catalog/'.$filePath);
    }

    /**
     * Generates a unique file name to avoid conflicts.
     *
     * @param  string  $savePath
     * @param  string  $originName
     * @return string
     */
    public function getUniqueFileName(string $savePath, string $originName): string
    {
        $fullPath = public_path("catalog{$this->basePath}{$savePath}/{$originName}");
        if (file_exists($fullPath)) {
            $originName = $this->getNewFileName($originName);

            return $this->getUniqueFileName($savePath, $originName);
        }

        return $originName;
    }

    /**
     * Generates a new file name by appending an incremented index.
     *
     * @param  string  $originName
     * @return string
     */
    public function getNewFileName(string $originName): string
    {
        $extension = pathinfo($originName, PATHINFO_EXTENSION);
        $name      = pathinfo($originName, PATHINFO_FILENAME);

        if (preg_match('/(.+?)\((\d+)\)$/', $name, $matches)) {
            $index = (int) $matches[2] + 1;
            $name  = "{$matches[1]}({$index})";
        } else {
            $name .= '(1)';
        }

        return "{$name}.{$extension}";
    }

    /**
     * @param  $filePath
     * @param  $baseName
     * @return array
     * @throws Exception
     */
    protected function handleImage($filePath, $baseName): array
    {
        $path     = "catalog{$filePath}";
        $realPath = str_replace($this->fileBasePath.$this->basePath, $this->fileBasePath, $this->fileBasePath.$filePath);

        $mime = '';
        if (file_exists($realPath)) {
            $mime = mime_content_type($realPath);
        }

        if (str_starts_with($mime, 'application/')) {
            $path = 'images/panel/doc.png';
        } elseif (str_starts_with($mime, 'video/')) {
            $path = 'images/panel/video.png';
        }

        return [
            'path'       => '/'.$path,
            'name'       => $baseName,
            'origin_url' => image_origin($path),
            'url'        => image_resize($path),
            'mime'       => $mime,
            'selected'   => false,
        ];
    }

    /**
     * @param  $folderPath
     * @param  $folderName
     * @return array
     */
    protected function handleFolder($folderPath, $folderName): array
    {
        return [
            'name' => $folderName,
            'path' => $folderPath,
        ];
    }

    /**
     * Copies multiple files to a new directory.
     *
     * @param  array  $files
     * @param  string  $destPath
     * @return bool
     * @throws Exception
     */
    public function copyFiles(array $files, string $destPath): bool
    {
        try {
            if (empty($files)) {
                throw new Exception(trans('panel/file_manager.no_files_selected'));
            }

            $destFullPath = public_path("catalog/{$destPath}");
            if (! is_dir($destFullPath)) {
                throw new Exception(trans('panel/file_manager.target_dir_not_exist'));
            }

            foreach ($files as $fileName) {
                $sourcePath   = public_path("catalog/{$fileName}");
                $destFilePath = rtrim($destFullPath, '/').'/'.basename($fileName);

                \Log::info('Copying file:', [
                    'source'      => $sourcePath,
                    'destination' => $destFilePath,
                ]);

                if (file_exists($sourcePath)) {
                    if (file_exists($destFilePath)) {
                        $newName      = $this->getUniqueFileName($destPath, basename($fileName));
                        $destFilePath = rtrim($destFullPath, '/').'/'.$newName;
                    }

                    if (! @copy($sourcePath, $destFilePath)) {
                        \Log::error('Failed to copy file:', [
                            'source'      => $sourcePath,
                            'destination' => $destFilePath,
                            'error'       => error_get_last(),
                        ]);
                        throw new Exception(trans('panel/file_manager.copy_failed'));
                    } else {
                        \Log::info('File copied successfully:', [
                            'from' => $sourcePath,
                            'to'   => $destFilePath,
                        ]);
                    }
                } else {
                    \Log::warning('Source file not found:', ['path' => $sourcePath]);
                    throw new Exception(trans('panel/file_manager.source_file_not_exist'));
                }
            }

            return true;
        } catch (Exception $e) {
            \Log::error('Copy files failed:', [
                'error'       => $e->getMessage(),
                'files'       => $files,
                'destination' => $destPath,
            ]);
            throw $e;
        }
    }
}
