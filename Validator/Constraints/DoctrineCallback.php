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
        if (is_array($options)) {
            $this->initArraySingleOption($options);
            $this->initArrayCallbackOption($options);
        }

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
     * @param array $options
     *
     * @return array
     */
    protected function initArraySingleOption(array $options)
    {
        if (1 === count($options) && isset($options['value'])) {
            $options = $options['value'];
        }

        return $options;
    }

    /**
     * Init callback options.
     *
     * @param array $options
     *
     * @return array
     */
    protected function initArrayCallbackOption($options)
    {
        if (!isset($options['callback']) && !isset($options['groups']) && is_callable($options)) {
            $options = array('callback' => $options);
        }

        return $options;
    }
}
