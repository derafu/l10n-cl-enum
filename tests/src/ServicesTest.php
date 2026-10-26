<?php

declare(strict_types=1);

/**
 * Derafu: L10n CL Enum - Chilean Enum Repository.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\L10n\Cl\TestsEnum;

use Derafu\L10n\Cl\Enum\Translation\L10nClEnumTranslationResourceProvider;
use Derafu\Translation\TranslatorFactory;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * Import the provider-only YAML and consume its tagged translation resources.
 */
#[CoversNothing]
final class ServicesTest extends TestCase
{
    private function load(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 2) . '/resources/config')))
            ->load('l10n-cl-enum-services.yaml');

        return $container;
    }

    public function testTheConfiguredProviderCanBeConstructed(): void
    {
        $container = $this->load();
        $container->getDefinition(L10nClEnumTranslationResourceProvider::class)->setPublic(true);
        $container->compile();
        $provider = $container->get(L10nClEnumTranslationResourceProvider::class);
        $this->assertInstanceOf(L10nClEnumTranslationResourceProvider::class, $provider);
        $directories = [...$provider->getDirectories()];
        $this->assertCount(1, $directories);
        $this->assertSame(realpath(dirname(__DIR__, 2) . '/resources/translations'), realpath($directories[0]));
    }

    public function testTaggedResourcesTranslateTheCataloguesMessagesWithTheirParameters(): void
    {
        $container = $this->load();
        $this->assertArrayHasKey(
            L10nClEnumTranslationResourceProvider::class,
            $container->findTaggedServiceIds('derafu_translation.resource_provider')
        );
        $container->register('app.translator', Translator::class)
            ->setFactory([TranslatorFactory::class, 'create'])
            ->setArguments(['es', ['en'], new TaggedIteratorArgument('derafu_translation.resource_provider')])
            ->setPublic(true);
        $container->compile();
        $translator = $container->get('app.translator');
        $this->assertInstanceOf(Translator::class, $translator);

        // Valid enums cannot trigger these integrity errors. Test catalogue
        // consumption without manufacturing inconsistent geographic data.
        foreach ([
            ['No region found for the province {province}.', ['province' => 'Test province'], 'No se encontró la región de la provincia Test province.'],
            ['Region not found for the commune {commune}.', ['commune' => 'Test commune'], 'No se encontró la región de la comuna Test commune.'],
            ['Province not found for the commune {commune}.', ['commune' => 'Test commune'], 'No se encontró la provincia de la comuna Test commune.'],
        ] as [$message, $parameters, $expected]) {
            $this->assertSame($expected, $translator->trans($message, $parameters, 'errors'));
        }
    }
}
