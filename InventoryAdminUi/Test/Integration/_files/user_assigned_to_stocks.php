<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Authorization\Model\Acl\Role\Group;
use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\Role;
use Magento\Authorization\Model\UserContextInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\User\Model\User;
use Magento\Authorization\Model\RulesFactory;
use Magento\Authorization\Model\Rules;

//Creating a new admin user with a custom role to safely change role settings without affecting the main user's role.
/** @var Role $role */
$role = Bootstrap::getObjectManager()->get(RoleFactory::class)->create();
$role->setName('stocks_access_role');
$role->setData('role_name', $role->getName());
$role->setRoleType(Group::ROLE_TYPE);
$role->setUserType((string)UserContextInterface::USER_TYPE_ADMIN);
$role->save();
/** @var Rules $rules */
$rules = Bootstrap::getObjectManager()->get(RulesFactory::class)->create();
$rules->setRoleId($role->getId());
$rules->setResources(
    [
        'Magento_Backend::stores',
        'Magento_InventoryApi::inventory',
        'Magento_InventoryApi::stock',
    ]
);
$rules->saveRel();

/** @var User $user */
$user = Bootstrap::getObjectManager()->create(User::class);
$user->setFirstname("John")
    ->setLastname("Doe")
    ->setUsername('stocksAccessUser')
    ->setPassword(\Magento\TestFramework\Bootstrap::ADMIN_PASSWORD)
    ->setEmail('adminUser@example.com')
    ->setIsActive(1)
    ->setRoleId($role->getId());
$user->save();
