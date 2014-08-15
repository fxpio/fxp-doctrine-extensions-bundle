<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\DoctrineExtensionsBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"CLASS", "PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DoctrineCallback extends Constraint
{
    /**
     * @var string|callable
     */
    public $callback;

    /**
     * {@inheritdoc}
     */
    public function __construct($options = null)
    {
        $this->initArraySingleOption($options);
        $this->initArrayCallbackOption($options);

        parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'callback';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return array(self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT);
    }

    /**
     * Invocation through annotations with an array parameter only.
     *
     * @param mixed|null $options
     *
     * @return mixed
     */
    protected function initArraySingleOption($options)
    {
        if (is_array($options) && 1 === count($options) && isset($options['value'])) {
            $options = $options['value'];
        }

        return $options;
    }

    /**
     * Init callback options.
     *
     * @param mixed $options
     *
     * @return array|mixed
     */
    protected function initArrayCallbackOption($options)
    {
        if (is_array($options) && !isset($options['callback']) && !isset($options['groups'])
                && is_callable($options)) {
            $options = array('callback' => $options);
        }

        return $options;
    }
}
