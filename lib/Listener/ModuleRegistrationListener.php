<?php
/** SPDX-FileCopyrightText: 2026 LibreCode Coop and contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later */
declare(strict_types=1);
namespace OCA\LibreFinanceNfse\Listener;
use OCA\LibreFinance\PublicAPI\ModuleDescriptor;
use OCA\LibreFinance\PublicAPI\RegisterModulesEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
/** @template-implements IEventListener<RegisterModulesEvent> */
final class ModuleRegistrationListener implements IEventListener {
    public function handle(Event $event):void {
        if($event instanceof RegisterModulesEvent){
            $event->register(new ModuleDescriptor('librefinance_nfse',
                'NFS-e Nacional (rascunhos)','Preparacao de documentos de servicos; emissao ainda nao habilitada',
                'librefinance_nfse.page.index'));
        }
    }
}
