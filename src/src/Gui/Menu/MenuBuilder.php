<?php

declare(strict_types=1);

/*
 * This file is part of the pseudify database pseudonymizer project
 * - (c) 2025 waldhacker UG (haftungsbeschränkt)
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Waldhacker\Pseudify\Core\Gui\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class MenuBuilder
{
    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @param array<array-key, mixed> $options
     */
    public function createMainMenu(array $options): ItemInterface
    {
        $session = $this->requestStack->getSession();
        $hasActiveProfile = null !== $session->get('profileDefinition', null);

        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttributes([
            'class' => 'nav nav-pills nav-sidebar flex-column',
            'data-widget' => 'treeview',
            'role' => 'menu',
            'data-accordion' => 'false',
        ]);

        $menu->addChild('Information', [
            'route' => 'app_information_information',
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link'],
            'extras' => ['iconClass' => 'fa-circle-info'],
        ]);

        $menu->addChild('Profiles', [
            'attributes' => ['class' => 'nav-item'],
            'labelAttributes' => ['class' => 'nav-link'],
            'extras' => ['iconClass' => 'fa-rectangle-list'],
            'childrenAttributes' => ['class' => 'nav nav-treeview'],
        ]);

        $menu['Profiles']->addChild('Load / Create', [
            'route' => 'app_profile_load',
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link'],
            'extras' => ['iconClass' => 'fa-upload'],
        ]);

        if ($hasActiveProfile) {
            $menu['Profiles']->addChild('Save', [
                'route' => 'app_profile_save',
                'attributes' => ['class' => 'nav-item'],
                'linkAttributes' => ['class' => 'nav-link'],
                'extras' => ['iconClass' => 'fa-download'],
            ]);
        }

        if ($hasActiveProfile) {
            $menu->addChild('Configuration', [
                'attributes' => ['class' => 'nav-item'],
                'labelAttributes' => ['class' => 'nav-link'],
                'extras' => ['iconClass' => 'fa-gear'],
                'childrenAttributes' => ['class' => 'nav nav-treeview'],
            ]);

            $menu['Configuration']->addChild('Basic configuration', [
                'route' => 'app_configuration_basic',
                'attributes' => ['class' => 'nav-item'],
                'linkAttributes' => ['class' => 'nav-link'],
                'extras' => ['iconClass' => 'fa-screwdriver-wrench'],
            ]);
            $menu['Configuration']->addChild('Table configuration', [
                'route' => 'app_configuration_table',
                'attributes' => ['class' => 'nav-item'],
                'linkAttributes' => ['class' => 'nav-link'],
                'extras' => ['iconClass' => 'fa-database'],
            ]);
            $menu['Configuration']->addChild('Auto configuration', [
                'route' => 'app_configuration_auto',
                'attributes' => ['class' => 'nav-item'],
                'linkAttributes' => ['class' => 'nav-link'],
                'extras' => ['iconClass' => 'fa-wand-magic-sparkles'],
            ]);

            $menu->addChild('Pseudonymize', [
                'attributes' => ['class' => 'nav-item'],
                'labelAttributes' => ['class' => 'nav-link'],
                'extras' => ['iconClass' => 'fa-shield'],
                'childrenAttributes' => ['class' => 'nav nav-treeview'],
            ]);

            $menu['Pseudonymize']->addChild('Analyze database', [
                'route' => 'app_pseudonymize_analyze',
                'attributes' => ['class' => 'nav-item'],
                'linkAttributes' => ['class' => 'nav-link'],
                'extras' => ['iconClass' => 'fa-magnifying-glass-chart'],
            ]);

            $menu['Pseudonymize']->addChild('Pseudonymize database', [
                'route' => 'app_pseudonymize_pseudonymize',
                'attributes' => ['class' => 'nav-item'],
                'linkAttributes' => ['class' => 'nav-link'],
                'extras' => ['iconClass' => 'fa-dice-d20'],
            ]);
        }

        $menu->addChild('Docs', [
            'uri' => 'https://www.pseudify.me/docs/current/',
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link', 'target' => '_blank'],
            'extras' => ['iconClass' => 'fa-book'],
        ]);

        return $menu;
    }
}
