<?php

/**
 * @author Difengo SAS
 * @copyright Copyright (c) 2018 Difengo SAS (http://www.difengo.com)
 * @package Difengo_Login
 */

use Magento\Framework\Component\ComponentRegistrar; 

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Difengo_Login',
    __DIR__
);