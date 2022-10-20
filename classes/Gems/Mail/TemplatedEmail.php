<?php

declare(strict_types=1);

namespace Gems\Mail;

use League\HTMLToMarkdown\HtmlConverter;
use Mezzio\Template\TemplateRendererInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class TemplatedEmail extends Email
{
    protected $converter;

    /**
     * @var TemplateRendererInterface
     */
    protected ?TemplateRendererInterface $template;


    public function __construct(TemplateRendererInterface $template, Headers $headers = null, AbstractPart $body = null)
    {
        parent::__construct($headers, $body);
        $this->template = $template;

        if (class_exists(HtmlConverter::class)) {
            $this->converter = new HtmlConverter([
                'hard_break' => true,
                'strip_tags' => true,
                'remove_nodes' => 'head style',
            ]);
        }
    }


    public function subject(string $subject, array $variables = []): static
    {
        if (count($variables)) {
            $twigLoader = new ArrayLoader([
                'subject' => $subject,
            ]);

            $twig = new Environment($twigLoader, [
                'autoescape' => false,
            ]);

            $subject = $twig->render('subject', $variables);
        }
        return parent::subject($subject);
    }

    /**
     * Set a twig template as html and text
     *
     * @param string $templateName
     * @param array $variables
     * @return $this
     * @throws \Exception
     */
    public function htmlTemplate(string $templateName, string $mailText, array $variables = [])
    {
        if (!$this->template) {
            throw new \Exception('No Template renderer set');
        }

        $variables['content'] = $mailText;

        $html = $this->template->render($templateName, $variables);
        $this->html($html);

        $text = $this->convertHtmlToText($html);
        $this->text($text);

        return $this;
    }

    /**
     * Convert Html to text
     *
     * @param $html
     * @return string
     */
    protected function convertHtmlToText($html)
    {
        if (null !== $this->converter) {
            return $this->converter->convert($html);
        }

        return strip_tags(preg_replace('{<(head|style)\b.*?</\1>}i', '', $html));
    }

}
