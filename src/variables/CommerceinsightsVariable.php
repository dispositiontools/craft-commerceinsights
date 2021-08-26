<?php
/**
 * Commerceinsights plugin for Craft CMS 3.x
 *
 * Get the low down on how your Commerce is doing
 *
 * @link      http://www.disposition.tools/
 * @copyright Copyright (c) 2021 Disposition Tools
 */

namespace dispositiontools\commerceinsights\variables;

use dispositiontools\commerceinsights\Commerceinsights;

use Craft;

/**
 * Commerceinsights Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.commerceinsights }}).
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    Disposition Tools
 * @package   Commerceinsights
 * @since     1.0.0
 */
class CommerceinsightsVariable
{
    // Public Methods
    // =========================================================================


    /**
     * Whatever you want to output to a Twig template can go into a Variable method.
     * You can have as many variable functions as you want.  From any Twig template,
     * call it like this:
     *
     *     {{ craft.commerceinsights.transactions($options) }}
     *
     * Or, if your variable requires parameters from Twig:
     *
     *     {{ craft.commerceinsights.transactions(twigValue) }}
     *
     * @param null $optional
     * @return string
     */
    public function transactions($options = [])
    {
        $results = Commerceinsights::$plugin->transactions->getTransactions($options);
        return $results;
    }
}
