<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Cache;

use Piwik\Cache;
use Piwik\Cache\Backend;
use RuntimeException;

/**
 * This class is used to cache data on the filesystem.
 *
 * This cache uses one file for all keys. We will load the cache file only once.
 *
 * $multi = new Multi();
 *
 * if (!$multi->isPopulated()) {
 *   $multi->populateCache($backend, $storageId = 'multicache');
 *   // $multi->get('my'id')
 *   // $multi->set('myid', 'test');
 *
 *   // ... at the end of the request
 *   $multi->persistCacheIfNeeded(43200);
 * }
 */
class Multi
{
    /**
     * @var Backend
     */
    private $storage;
    private $storageId;
    private $content;
    private $isDirty = false;

    /**
     * Get the content related to the current cache key. Make sure to call the method {@link has()} to verify whether
     * there is actually any content set under this cache key.
     * @return mixed
     */
    public function get($id)
    {
        return $this->content[$id];
    }

    /**
     * Check whether any content was actually stored for the current cache key.
     * @return bool
     */
    public function has($id)
    {
        return array_key_exists($id, $this->content);
    }

    /**
     * Set (overwrite) any content related to the current set cache key.
     * @param $content
     * @return boolean
     */
    public function set($id, $content)
    {
        $this->content[$id] = $content;
        $this->isDirty = true;
        return true;
    }

    /**
     * Deletes a cache entry.
     *
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    public function delete($id)
    {
        if ($this->has($id)) {
            unset($this->content[$id]);
            return true;
        }

        return false;
    }

    /**
     * Flushes all cache entries.
     *
     * @return boolean TRUE if the cache entries were successfully flushed, FALSE otherwise.
     */
    public function flushAll()
    {
        if ($this->isPopulated()) {
            $this->storage->doDelete($this->storageId);
        }

        $this->content = array();

        return true;
    }

    public function populateCache(Backend $storage, $storageId)
    {
        $this->content = array();
        $this->storage = $storage;
        $this->storageId = $storageId;

        $content = $storage->doFetch($storageId);

        if (is_array($content)) {
            $this->content = $content;
        }
    }

    public function isPopulated()
    {
        return !is_null($this->storage) && !is_null($this->storageId);
    }

    public function persistCacheIfNeeded($ttl)
    {
        if (!$this->isPopulated()) {
            throw new RuntimeException('Cache was never populated. Make sure to call populateCache() first');
        }

        if ($this->isDirty) {
            $this->storage->doSave($this->storageId, $this->content, $ttl);
        }
    }

}
