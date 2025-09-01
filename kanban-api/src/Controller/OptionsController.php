<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OptionsController
{
    #[Route('/{path}', name: 'cors_options', requirements: ['path' => '.*'], methods: ['OPTIONS'])]
    public function options(): Response
    {
        // 204 No Content suffit, les headers CORS seront ajoutés par le subscriber
        return new Response('', 204);
    }
}
