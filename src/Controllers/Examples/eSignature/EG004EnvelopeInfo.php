<?php

/**
 * Example 004: Get an envelope's basic information and status
 */

namespace DocuSign\Controllers\Examples\eSignature;

use DocuSign\Controllers\eSignBaseController;
use DocuSign\Services\Examples\eSignature\EnvelopeInfoService;

class EG004EnvelopeInfo extends eSignBaseController
{
    const EG = 'eg004'; # reference (and URL) for this example
    const FILE = __FILE__;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        parent::controller();
    }

    /**
     * 1. Check the token and check we have an envelope_id
     * 2. Call the worker method
     *
     * @return void
     */
    public function createController(): void
    {
        $this->checkDsToken();
        $envelope_id = $this->args['envelope_id'];
        if ($envelope_id) {
            # 2. Call the worker method
            # More data validation would be a good idea here
            # Strip anything other than characters listed
            $envelope = EnvelopeInfoService::envelopeInfo($this->args, $this->clientService);

            if ($envelope) {
                # results is an object that implements ArrayAccess. Convert to a regular array:
                $envelope = json_decode((string)$envelope, true);
                $this->clientService->showDoneTemplateFromManifest(
                    $this->codeExampleText,
                    json_encode(json_encode($envelope))
                );
            }
        } else {
            $this->clientService->envelopeNotCreated(
                basename(__FILE__),
                $this->routerService->getTemplate($this::EG),
                $this->routerService->getTitle($this::EG),
                $this::EG,
                ['envelope_ok' => false]
            );
        }
    }

    /**
     * Get specific template arguments
     *
     * @return array
     */
    public function getTemplateArgs(): array
    {
        $envelope_id = $_SESSION['envelope_id'] ?? false;
        return [
            'account_id' => $_SESSION['ds_account_id'],
            'base_path' => $_SESSION['ds_base_path'],
            'ds_access_token' => $_SESSION['ds_access_token'],
            'envelope_id' => $envelope_id
        ];
    }
}
