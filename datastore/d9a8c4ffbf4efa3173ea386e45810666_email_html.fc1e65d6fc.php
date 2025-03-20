<?php

return <<<'VALUE'
"namespace IPS\\Theme;\n\tfunction email_html_core_digest( $member, $frequency, $email ) {\n\t\t$return = '';\n\t\t$return .= <<<IPSCONTENT\n\n\nIPSCONTENT;\n$return .= \\IPS\\Theme\\Template::htmlspecialchars( $email->language->addToStack(\"digest_$frequency\", FALSE), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<IPSCONTENT\n\n<br \/>\n<br \/>\n<em style='color: #8c8c8c'>&mdash; \nIPSCONTENT;\n\n$return .= \\IPS\\Settings::i()->board_name;\n$return .= <<<IPSCONTENT\n<\/em>\n\n<br \/>\n<br \/>\n<br \/>\n___digest___\nIPSCONTENT;\n\n\t\treturn $return;\n}\n\tfunction email_plaintext_core_digest( $member, $frequency, $email ) {\n\t\t$return = '';\n\t\t$return .= <<<IPSCONTENT\n\n\nIPSCONTENT;\n$return .= \\IPS\\Theme\\Template::htmlspecialchars( $email->language->addToStack(\"digest_$frequency\", FALSE), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );\n$return .= <<<IPSCONTENT\n\n\n___digest___\nIPSCONTENT;\n\n\t\treturn $return;\n}"
VALUE;
