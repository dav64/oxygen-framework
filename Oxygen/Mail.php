<?php
class Oxygen_Mail
{
    CONST PRIORITY_MAXIMAL = 1;
    CONST PRIORITY_UPPER   = 2;
    CONST PRIORITY_NORMAL  = 3;
    CONST PRIORITY_LOWER   = 4;
    CONST PRIORITY_MINIMAL = 5;

    public static $DEFAULT_FROM = '"PostMaster" <postmaster@domain.net>';

    public static function sendmail(
        $to,
        $subject,
        $message,
        $isHTML = true,
        $attachments = array(),
        $cc = array(),
        $bcc = array(),
        $priority = null,
        $replyto = null,
        $from = null
    )
    {
        $headers = array();
        $content = array();

        $line_feed = "\r\n";

        if (empty($replyto))
        {
            $config = Config::getInstance();
            $replyto = $config->getOption('mailFrom');
        }

        if (empty($from))
        {
            $config = Config::getInstance();
            $from = $config->getOption('mailFrom');
        }

        // Setting boundaries limiters
        $boundary     = "=_".md5(rand());
        $boundary_alt = "=_".md5(rand());

        // Headers
        $headers[] = "From: ".$from;
        $headers[] = "Reply-to: ".$replyto;
        $headers[] = "MIME-Version: 1.0";

        if (!empty($priority))
            $headers[] = "X-Priority: ".$priority;

        $mime = empty($attachments) ? 'multipart/alternative' : 'multipart/mixed';
        $headers[] = "Content-Type: $mime;"." boundary=\"$boundary\"";

        // Handling message content
        $content[] = '';
        $content[] = "--".$boundary;

        if ($isHTML)
        {
            $content[] = "Content-Type: text/html; charset=\"ISO-8859-1\"";
            $content[] = "Content-Transfer-Encoding: 8bit";
        }
        else
        {
            $content[] = "Content-Type: text/plain; charset=\"ISO-8859-1\"";
            $content[] = "Content-Transfer-Encoding: 8bit";
        }

        $content[] = '';
        $content[] = $message;

        /// ----------------------------------

        // adding attachments
        if (!empty($attachments))
        {
            foreach($attachments as $mimetype => $filepath)
            {
                // todo: filedata array (mime => filename)
                $file   = fopen($filepath, "r");
                $mimetype = (false !== strpos($mimetype, '/')) ? $mimetype : 'application/octet-stream';

                // File not found, dismiss
                if (empty($file))
                    continue;

                $attachement = @fread($file, filesize($filepath));
                fclose($file);

                // File empty, dismiss
                if (empty($attachement))
                    continue;

                $attachement = chunk_split(base64_encode($attachement));

                $filename = basename($filepath);

                // Add attachment into content
                $content[] = '';
                $content[] = "--".$boundary;
                $content[] = "Content-Type: $mimetype; name=\"$filename\"";
                $content[] = "Content-Transfer-Encoding: base64";
                $content[] = "Content-Disposition: attachment; filename=\"$filename\"";
                $content[] = '';
                $content[] = $attachement;
            }
        }

        $content[] = '';
        $content[] = "--".$boundary."--";
        $content[] = '';

        mail(
            $to,
            $subject,
            implode($line_feed, $content),
            implode($line_feed, $headers)
        );
    }
}