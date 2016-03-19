<?php
declare(strict_types=1);
namespace Khepin\Partibus;

/**
 * Token class made to represent unique names.
 * Cannot be instantiated or cloned. The only way to create a t (meaning `tag` or `token`,
 * but also alike `keyword` in some other languages) is through `t::n($name);`
 *
 * Instances are unique, therefore `t::n('hello') === t::n('hello') // -> true`
 */
class t {
    /**
     * All existing instances of t, in order to ensure unicity
     * @var array
     */
    private static $instances = [];

    /**
     * The t's name
     * @var string
     */
    private $name = '';

    /**
     * Prevent direct instanciation
     * @param string $name
     */
    private function __construct(string $name) {
        $this->name = $name;
    }

    /**
     * Create a new token
     * @param  string $name
     * @return \Khepin\Partibus\t
     */
    public static function n(string $name) : t {
        $name = (string) $name;
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new self($name);
        }

        return self::$instances[$name];
    }

    /**
     * Nice string representation
     * @return string [description]
     */
    public function __toString() : string {
        return sprintf('t::n(%s)', $this->name);
    }

    /**
     * Prevent cloning
     */
    private function __clone(){}

    public function name() : string {
        return $this->name;
    }
}