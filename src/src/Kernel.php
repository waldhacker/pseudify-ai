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

namespace Waldhacker\Pseudify\Core;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Waldhacker\Pseudify\Core\DependencyInjection\PseudifyPass;
use Waldhacker\Pseudify\Core\Faker\FakeDataProviderInterface;
use Waldhacker\Pseudify\Core\Processor\Encoder\EncoderInterface;
use Waldhacker\Pseudify\Core\Processor\Processing\ExpressionLanguage\ConditionExpressionProviderInterface;
use Waldhacker\Pseudify\Core\Profile\Analyze\ProfileInterface as AnalyzeProfileInterface;
use Waldhacker\Pseudify\Core\Profile\Pseudonymize\ProfileInterface as PseudonymizeProfileInterface;

/**
 * @internal
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public const string USER_DATA_PATH = '/opt/pseudify/userdata';

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../config/{packages}/*.yaml');
        $container->import('../config/{packages}/'.(string) $this->environment.'/*.yaml');
        $container->import('../config/{services}.yaml');
        $container->import('../config/{services}_'.(string) $this->environment.'.yaml');

        if (\is_dir(self::USER_DATA_PATH.'/config/')) {
            $container->import(self::USER_DATA_PATH.'/config/*.yaml');
        }

        if (is_file($path = \dirname(__DIR__).'/config/services.php')) {
            (require $path)($container->withPath($path), $this);
        }
    }

    #[\Override]
    protected function build(ContainerBuilder $container): void
    {
        $container->getParameterBag()->add(['pseudify.data_dir' => self::USER_DATA_PATH]);

        $container->registerForAutoconfiguration(AnalyzeProfileInterface::class)
            ->addTag('pseudify.analyze.profile');
        $container->registerForAutoconfiguration(PseudonymizeProfileInterface::class)
            ->addTag('pseudify.pseudonymize.profile');
        $container->registerForAutoconfiguration(FakeDataProviderInterface::class)
            ->addTag('pseudify.faker.provider');
        $container->registerForAutoconfiguration(ConditionExpressionProviderInterface::class)
            ->addTag('pseudify.condition_expression_provider');
        $container->registerForAutoconfiguration(EncoderInterface::class)
            ->addTag('pseudify.encoder');

        $container->addCompilerPass(new PseudifyPass());
    }

    #[\Override]
    public function getCacheDir(): string
    {
        return self::USER_DATA_PATH.'/var/cache/'.(string) $this->environment;
    }

    #[\Override]
    public function getLogDir(): string
    {
        return self::USER_DATA_PATH.'/var/log';
    }
}
