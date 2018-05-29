<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            new Snc\RedisBundle\SncRedisBundle(),
            new WhiteOctober\PagerfantaBundle\WhiteOctoberPagerfantaBundle(),
            new Fa\Bundle\AdBundle\FaAdBundle(),
            new Fa\Bundle\UserBundle\FaUserBundle(),
            new Fa\Bundle\CoreBundle\FaCoreBundle(),
            new Fa\Bundle\AdFeedBundle\FaAdFeedBundle(),
            new Fa\Bundle\AdminBundle\FaAdminBundle(),
            new Fa\Bundle\ArchiveBundle\FaArchiveBundle(),
            new Fa\Bundle\PaymentBundle\FaPaymentBundle(),
            new JMS\TranslationBundle\JMSTranslationBundle(),
            new Lexik\Bundle\TranslationBundle\LexikTranslationBundle(),
            new Fa\Bundle\LexikTranslationBundle\FaLexikTranslationBundle(),
            new Fa\Bundle\FrontendBundle\FaFrontendBundle(),
            new Fa\Bundle\EmailBundle\FaEmailBundle(),
            new Fa\Bundle\EntityBundle\FaEntityBundle(),
            new Fa\Bundle\MessageBundle\FaMessageBundle(),
            new Fa\Bundle\PromotionBundle\FaPromotionBundle(),
            new Fa\Bundle\ContentBundle\FaContentBundle(),
            // new Pinano\Select2Bundle\PinanoSelect2Bundle(),
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            new Fa\Bundle\DotMailerBundle\FaDotMailerBundle(),
            new Fa\Bundle\ReportBundle\FaReportBundle(),
            new Fa\Bundle\TiReportBundle\FaTiReportBundle(),
            new AppBundle\AppBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test', 'live_dev', 'live_test'], true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();

            if ('dev' === $this->getEnvironment()) {
                $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
                $bundles[] = new Symfony\Bundle\WebServerBundle\WebServerBundle();
            }
        }

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__).'/var/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->setParameter('container.autowiring.strict_mode', true);
            $container->setParameter('container.dumper.inline_class_loader', true);

            $container->addObjectResource($this);
        });
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }
}
