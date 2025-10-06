<?php

/**
 * This file is part of the bitrix24-php-sdk package.
 *
 * © Dmitriy Ignatenko <titarx@gmail.com>
 *
 * For the full copyright and license information, please view the MIT-LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bitrix24\SDK\Services\CRM\Documentgenerator\Numerator\Result;

use Bitrix24\SDK\Core\Result\DeletedItemBatchResult;

/**
 * Class DeletedNumeratorBatchResult
 *
 * @package Bitrix24\SDK\Services\CRM\Documentgenerator\Numerator\Result
 */
class DeletedNumeratorBatchResult extends DeletedItemBatchResult
{
    public function isSuccess(): bool
    {
        return (bool)$this->getResponseData()->getResult();
    }
}
