<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Waldhacker\Pseudify\Core\Processor\Encoder\EncoderCollection;
use Waldhacker\Pseudify\Core\Processor\Processing\ExpressionLanguage\ConditionExpressionProviderCollection;
use Waldhacker\Pseudify\Core\Profile\Analyze\ProfileCollection as AnalyzeProfileCollection;
use Waldhacker\Pseudify\Core\Profile\Pseudonymize\ProfileCollection as PseudonymizeProfileCollection;

return function (ContainerConfigurator $configurator) {
    $configurator->services()
        ->set(AnalyzeProfileCollection::class)->args([tagged_iterator('pseudify.analyze.profile')])
        ->set(PseudonymizeProfileCollection::class)->args([tagged_iterator('pseudify.pseudonymize.profile')])
        ->set(ConditionExpressionProviderCollection::class)->args([tagged_iterator('pseudify.condition_expression_provider')])
        ->set(EncoderCollection::class)->args([tagged_iterator('pseudify.encoder')])
    ;
};
