<?php
/**
 * Secure File Upload Helper
 * /helpers/FileUpload.php
 *
 * Provides secure file upload functionality with validation.
 * Prevents malicious file uploads and ensures file type integrity.
 */

class FileUpload {
    private $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    private $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    private $max_size = 5242880; // 5MB in bytes
    private $upload_dir = 'uploads/';

    /**
     * Upload a file securely
     *
     * @param array $file The $_FILES array element
     * @param string $type File type prefix (e.g., 'profile', 'proof')
     * @return string The path to the uploaded file
     * @throws Exception If upload fails or validation fails
     */
    public function upload($file, $type = 'file') {
        // Validate file exists and no upload error
        if (!isset($file) || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error. Please try again.");
        }

        // Check if file was actually uploaded via HTTP POST
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception("Invalid file upload.");
        }

        // Check file size
        if ($file['size'] > $this->max_size) {
            throw new Exception("File too large. Maximum size is 5MB.");
        }

        if ($file['size'] == 0) {
            throw new Exception("File is empty.");
        }

        // Validate MIME type using fileinfo (more secure than checking extension)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $this->allowed_types)) {
            throw new Exception("Invalid file type. Only JPG, PNG, and GIF images are allowed.");
        }

        // Double-check extension
        $original_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($original_ext, $this->allowed_extensions)) {
            throw new Exception("Invalid file extension.");
        }

        // Additional image validation
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            throw new Exception("File is not a valid image.");
        }

        // Generate secure random filename
        $ext = $this->getExtensionFromMime($mime);
        $filename = $type . '_' . bin2hex(random_bytes(16)) . '.' . $ext;
        $target = $this->upload_dir . $filename;

        // Ensure upload directory exists
        if (!is_dir($this->upload_dir)) {
            if (!mkdir($this->upload_dir, 0755, true)) {
                throw new Exception("Failed to create upload directory.");
            }
        }

        // Move file to target location
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new Exception("Failed to move uploaded file.");
        }

        // Set appropriate permissions
        chmod($target, 0644);

        return $target;
    }

    /**
     * Delete an uploaded file
     *
     * @param string $filepath Path to the file to delete
     * @return bool True on success, false on failure
     */
    public function delete($filepath) {
        // Security check: only allow deletion of files in upload directory
        $realpath = realpath($filepath);
        $upload_realpath = realpath($this->upload_dir);

        if ($realpath === false || strpos($realpath, $upload_realpath) !== 0) {
            error_log("Attempted to delete file outside upload directory: $filepath");
            return false;
        }

        if (file_exists($filepath) && is_file($filepath)) {
            return unlink($filepath);
        }

        return false;
    }

    /**
     * Get file extension from MIME type
     *
     * @param string $mime MIME type
     * @return string File extension
     */
    private function getExtensionFromMime($mime) {
        $mime_map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif'
        ];
        return $mime_map[$mime] ?? 'jpg';
    }

    /**
     * Set custom upload directory
     *
     * @param string $dir Directory path
     */
    public function setUploadDir($dir) {
        // Ensure trailing slash
        $this->upload_dir = rtrim($dir, '/') . '/';
    }

    /**
     * Set maximum file size
     *
     * @param int $size Size in bytes
     */
    public function setMaxSize($size) {
        $this->max_size = intval($size);
    }

    /**
     * Validate if a file path is safe and in upload directory
     *
     * @param string $filepath Path to validate
     * @return bool True if safe, false otherwise
     */
    public function isValidPath($filepath) {
        $realpath = realpath($filepath);
        $upload_realpath = realpath($this->upload_dir);

        if ($realpath === false || $upload_realpath === false) {
            return false;
        }

        return strpos($realpath, $upload_realpath) === 0;
    }
}
?>
