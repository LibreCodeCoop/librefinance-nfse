<?php
/** SPDX-FileCopyrightText: 2026 LibreCode Coop and contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later */
declare(strict_types=1);
namespace OCA\LibreFinanceNfse\AppInfo;
use OCA\LibreFinance\PublicAPI\RegisterModulesEvent;
use OCA\LibreFinanceNfse\Listener\ModuleRegistrationListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
final class Application extends App implements IBootstrap {
    public const APP_ID='librefinance_nfse';
    public function __construct(array $urlParams=[]) {parent::__construct(self::APP_ID,$urlParams);}
    public function register(IRegistrationContext $context):void {
        $context->registerEventListener(RegisterModulesEvent::class,ModuleRegistrationListener::class);
    }
    public function boot(IBootContext $context):void {}
}
