<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiChatbotController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    #[Route('/ai-chatbot', name: 'ai_chatbot', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('ai_chatbot/index.html.twig');
    }

    #[Route('/ai-chatbot', name: 'ai_chatbot_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        // Získaj otázku z formulára
        $question = $request->request->get('question');

        // Validácia
        if (empty($question)) {
            $this->addFlash('error', 'Prosím zadaj otázku!');
            return $this->redirectToRoute('ai_chatbot');
        }

        try {
            // Groq API request
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $_ENV['GROQ_API_KEY'],
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.1-8b-instant',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Si užitočný AI asistent. Odpovedaj česky, stručne a zrozumiteľne.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $question
                        ]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                ],
            ]);

            // Spracuj odpoveď
            $data = $response->toArray();
            $aiAnswer = $data['choices'][0]['message']['content'];

            // Zobraz výsledok
            return $this->render('ai_chatbot/result.html.twig', [
                'question' => $question,
                'answer' => $aiAnswer,
            ]);

            // Uložiť do session
            $session = $request->getSession();
            $history = $session->get('chat_history', []);
            $history[] = ['question' => $question, 'answer' => $aiAnswer];
            $session->set('chat_history', $history);



        } catch (\Exception $e) {
            $this->addFlash('error', 'Chyba pri komunikácii s AI: ' . $e->getMessage());
            return $this->redirectToRoute('ai_chatbot');
        }
    }
}