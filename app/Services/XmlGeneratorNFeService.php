<?php

namespace App\Services;

use App\Contracts\OutputGeneratorInterface;
use DOMDocument;
use DOMElement;

class XmlGeneratorNFeService implements OutputGeneratorInterface
{
    public function generateBatch(array $rpsList, string $loteId = '1', array $providerInfo = [], array $options = []): string
    {
        $state = $options['state'] ?? 'BA';
        $startingNumber = $options['starting_number'] ?? 1;
        return $this->generateBatchXml($rpsList, $loteId, $providerInfo, $state, $startingNumber);
    }

    public function getExtension(): string
    {
        return 'xml';
    }

    /**
     * Generate NF-e 4.0 XML for output invoices (Saídas)
     */
    public function generateBatchXml(array $rpsList, string $batchId, array $providerInfo = [], string $state = 'BA', int $startingNumber = 1): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Root element
        $root = $dom->createElement('nfeProc');
        $root->setAttribute('versao', '4.00');
        $root->setAttribute('xmlns', 'http://www.portalfiscal.inf.br/nfe');
        $dom->appendChild($root);
        $currentNumber = $startingNumber;
        $batchIdClean = preg_replace('/\D/', '', $batchId);

        \Log::info("XmlGeneratorNFeService: Starting Batch", [
            'batch_id' => $batchId,
            'starting_number' => $startingNumber,
            'rps_count' => count($rpsList)
        ]);

        foreach ($rpsList as $rps) {
            $cNF = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
            $chaveNFe = $this->generateChaveNFe($providerInfo, $state, $currentNumber, $rps, $cNF);

            $nfe = $this->createNFe($dom, $rps, $providerInfo, $state, $currentNumber, $chaveNFe, $cNF, $batchIdClean);
            $root->appendChild($nfe);
            $currentNumber++;
        }

        return $dom->saveXML();
    }

    /**
     * Generate individual NF-e XML files (one file per RPS)
     * Returns array of ['filename' => 'xml_content']
     */
    public function generateIndividualXmls(array $rpsList, string $batchId, array $providerInfo = [], string $state = 'BA', int $startingNumber = 1): array
    {
        $xmlFiles = [];
        $currentNumber = $startingNumber;

        foreach ($rpsList as $index => $rps) {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->formatOutput = true;

            // Root element for single NF-e
            $root = $dom->createElement('nfeProc');
            $root->setAttribute('versao', '4.00');
            $root->setAttribute('xmlns', 'http://www.portalfiscal.inf.br/nfe');
            $dom->appendChild($root);

            // Generate cNF and Key upfront for consistency
            $cNF = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
            $chaveNFe = $this->generateChaveNFe($providerInfo, $state, $currentNumber, $rps, $cNF);
            $batchIdClean = preg_replace('/\D/', '', $batchId);

            // Create single NF-e
            $nfe = $this->createNFe($dom, $rps, $providerInfo, $state, $currentNumber, $chaveNFe, $cNF, $batchIdClean);
            $root->appendChild($nfe);

            // Generate filename: NFe + chave (44 digits)
            $filename = 'NFe' . $chaveNFe . '.xml';

            $xmlFiles[$filename] = $dom->saveXML();
            $currentNumber++;
        }

        return $xmlFiles;
    }

    /**
     * Create an XML element with text content, properly escaping special characters
     */
    private function createElementWithText(DOMDocument $dom, string $name, string $value): DOMElement
    {
        $element = $dom->createElement($name);
        $element->appendChild($dom->createTextNode($value));
        return $element;
    }

    /**
     * Generate 44-digit NF-e key according to NF-e 4.0 specification
     */
    private function generateChaveNFe(array $providerInfo, string $state, int $number, $rps, string $cNF): string
    {
        // 1. UF (2 digits)
        $ufCode = $this->getUFCode($state);

        // 2. AAMM (4 digits)
        $dateInfo = $this->parseFlexibleDate($rps->dataEmissao);
        $aamm = $dateInfo['ym'] ?? date('ym');

        // 3. CNPJ (14 digits)
        $cnpj = preg_replace('/\D/', '', $providerInfo['cnpj'] ?? '12314872000103');
        $cnpj = str_pad($cnpj, 14, '0', STR_PAD_LEFT);

        // 4. Modelo (2 digits) - 44 as requested by the user for Saídas
        $mod = '44';

        // 5. Série (3 digits)
        $serie = str_pad('1', 3, '0', STR_PAD_LEFT);

        // 6. Número (9 digits)
        $nNF = str_pad((string) $number, 9, '0', STR_PAD_LEFT);

        // 7. Tipo Emissão (1 digit)
        $tpEmis = '1';

        // 8. Código Numérico (8 digits)
        $cNF_padded = str_pad($cNF, 8, '0', STR_PAD_LEFT);

        $chaveSemDV = $ufCode . $aamm . $cnpj . $mod . $serie . $nNF . $tpEmis . $cNF_padded;
        $dv = $this->calculateMod11($chaveSemDV);

        return $chaveSemDV . $dv;
    }

    /**
     * Calculate Módulo 11 check digit for NF-e key
     */
    private function calculateMod11(string $chave): int
    {
        $peso = 2;
        $soma = 0;

        for ($i = strlen($chave) - 1; $i >= 0; $i--) {
            $soma += intval($chave[$i]) * $peso;
            $peso = ($peso == 9) ? 2 : $peso + 1;
        }

        $resto = $soma % 11;
        $dv = ($resto == 0 || $resto == 1) ? 0 : 11 - $resto;

        return $dv;
    }

    private function createNFe(DOMDocument $dom, $rps, array $providerInfo, string $state, int $number, string $chaveNFe, string $cNF, string $batchId = ''): DOMElement
    {
        $nfe = $dom->createElement('NFe');

        // infNFe
        $infNFe = $dom->createElement('infNFe');
        $infNFe->setAttribute('Id', 'NFe' . $chaveNFe);
        $infNFe->setAttribute('versao', '4.00');

        // IDE - Identificação
        $cDV = substr($chaveNFe, -1);
        $mod = substr($chaveNFe, 20, 2); // Extract model from key (55 or 44)
        $ide = $this->createIde($dom, $rps, $state, $number, $cNF, $cDV, $mod);
        $infNFe->appendChild($ide);

        // Emit - Emitente
        $emit = $this->createEmit($dom, $providerInfo);
        $infNFe->appendChild($emit);

        // Dest - Destinatário
        $dest = $this->createDest($dom, $rps);
        $infNFe->appendChild($dest);

        // Det - Detalhamento (Produto/Serviço)
        $det = $this->createDet($dom, $rps, $state);
        $infNFe->appendChild($det);

        // Total
        $total = $this->createTotal($dom, $rps);
        $infNFe->appendChild($total);

        // Transp - Transporte
        $transp = $this->createTransp($dom);
        $infNFe->appendChild($transp);

        // Pag - Pagamento
        $pag = $this->createPag($dom, $rps);
        $infNFe->appendChild($pag);

        // infAdic - Informações Adicionais (Prevent auto-post to ICMS segment)
        $infAdic = $this->createInfAdic($dom, $batchId);
        $infNFe->appendChild($infAdic);

        $nfe->appendChild($infNFe);

        return $nfe;
    }

    private function createIde(DOMDocument $dom, $rps, string $state, int $number, string $cNF, string $cDV, string $mod = '44'): DOMElement
    {
        $ide = $dom->createElement('ide');

        $ide->appendChild($dom->createElement('cUF', $this->getUFCode($state)));
        $ide->appendChild($dom->createElement('cNF', '')); // Empty content as requested
        $ide->appendChild($dom->createElement('natOp', 'RECEBIMENTO DE ALUGUEIS'));
        $ide->appendChild($dom->createElement('mod', $mod)); // Modelo 44 (Requested)
        $ide->appendChild($dom->createElement('serie', '1'));
        $ide->appendChild($dom->createElement('nNF', $number));

        // Data/Hora de emissão
        $dateParts = $this->parseFlexibleDate($rps->dataEmissao);
        $dhEmi = $dateParts['iso_tz'] ?? date('Y-m-d') . 'T12:00:00-03:00';
        $ide->appendChild($dom->createElement('dhEmi', $dhEmi));

        $ide->appendChild($dom->createElement('tpNF', '1')); // 1=Saída
        $ide->appendChild($dom->createElement('idDest', '1')); // 1=Operação interna
        $ide->appendChild($dom->createElement('cMunFG', '2927408')); // Salvador
        $ide->appendChild($dom->createElement('tpImp', '1')); // 1=Retrato
        $ide->appendChild($dom->createElement('tpEmis', '1')); // 1=Normal
        $ide->appendChild($dom->createElement('cDV', $cDV));
        $ide->appendChild($dom->createElement('tpAmb', '2')); // 2=Homologação
        $ide->appendChild($dom->createElement('finNFe', '1')); // 1=Normal
        $ide->appendChild($dom->createElement('indFinal', '0')); // 0=Normal
        $ide->appendChild($dom->createElement('indPres', '0')); // 0=Não se aplica
        $ide->appendChild($dom->createElement('procEmi', '0')); // 0=Emissão própria
        $ide->appendChild($dom->createElement('verProc', '1.0'));

        return $ide;
    }

    private function createEmit(DOMDocument $dom, array $providerInfo): DOMElement
    {
        $emit = $dom->createElement('emit');

        $cnpj = $providerInfo['cnpj'] ?? '12314872000103';
        $emit->appendChild($dom->createElement('CNPJ', preg_replace('/\D/', '', $cnpj)));
        $emit->appendChild($this->createElementWithText($dom, 'xNome', $providerInfo['razao_social'] ?? 'PRESTADOR DE SERVICOS'));
        $emit->appendChild($this->createElementWithText($dom, 'xFant', $providerInfo['razao_social'] ?? 'PRESTADOR'));

        // Endereço - usando dados reais da RFB
        $enderEmit = $dom->createElement('enderEmit');

        // Parse endereço (pode vir como "RUA EXEMPLO, 123")
        $enderecoCompleto = $providerInfo['endereco'] ?? 'RUA EXEMPLO, 123';
        $enderecoParts = explode(',', $enderecoCompleto);
        $logradouro = trim($enderecoParts[0] ?? 'RUA EXEMPLO');
        $numero = trim($enderecoParts[1] ?? '123');

        $enderEmit->appendChild($this->createElementWithText($dom, 'xLgr', $logradouro));
        $enderEmit->appendChild($this->createElementWithText($dom, 'nro', $numero));
        $enderEmit->appendChild($this->createElementWithText($dom, 'xBairro', $providerInfo['bairro'] ?? 'CENTRO'));

        // Código do município (hardcoded para Salvador por enquanto)
        $enderEmit->appendChild($dom->createElement('cMun', '2927408'));
        $enderEmit->appendChild($this->createElementWithText($dom, 'xMun', $providerInfo['municipio'] ?? 'Salvador'));
        $enderEmit->appendChild($dom->createElement('UF', $providerInfo['uf'] ?? 'BA'));

        $cep = preg_replace('/\D/', '', $providerInfo['cep'] ?? '40000000');
        $enderEmit->appendChild($dom->createElement('CEP', $cep));
        $enderEmit->appendChild($dom->createElement('cPais', '1058'));
        $enderEmit->appendChild($dom->createElement('xPais', 'Brasil'));

        // Adicionar telefone se disponível
        if (!empty($providerInfo['fone'])) {
            $fone = preg_replace('/\D/', '', $providerInfo['fone']);
            if (strlen($fone) >= 10) {
                $ddd = substr($fone, 0, 2);
                $telefone = substr($fone, 2);
                $enderEmit->appendChild($dom->createElement('fone', $ddd . $telefone));
            }
        }

        $emit->appendChild($enderEmit);

        $emit->appendChild($dom->createElement('IE', '000000000'));
        $emit->appendChild($dom->createElement('CRT', '1')); // 1=Simples Nacional

        return $emit;
    }

    private function createDest(DOMDocument $dom, $rps): DOMElement
    {
        $dest = $dom->createElement('dest');

        $cpfCnpj = preg_replace('/\D/', '', $rps->tomador->cpfCnpj);

        // Determine tag based on length (CPF=11, CNPJ=14)
        if (strlen($cpfCnpj) === 11) {
            $dest->appendChild($dom->createElement('CPF', $cpfCnpj));
        } else {
            // Default to CNPJ if length is 14 or unknown (Domínio usually validates this)
            $dest->appendChild($dom->createElement('CNPJ', $cpfCnpj));
        }

        $xNome = !empty($rps->tomador->razaoSocial) ? $rps->tomador->razaoSocial : 'CLIENTE SEM NOME';

        // Sanitize xNome: Uppercase, Remove Accents and leading numeric garbage
        $xNome = $this->sanitizeText($xNome);
        $xNome = preg_replace('/^[\d\.,\s]+/', '', $xNome);

        $dest->appendChild($this->createElementWithText($dom, 'xNome', $xNome));

        // Endereço (Forced Fallback for placeholders like "NAO INFORMADO")
        $logradouro = $this->getValidValue($rps->tomador->endereco->logradouro ?? '', 'RUA DO CLIENTE');
        $bairro = $this->getValidValue($rps->tomador->endereco->bairro ?? '', 'CENTRO');
        $numero = $this->getValidValue($rps->tomador->endereco->numero ?? '', 'SN');
        $cep = $this->getValidValue($rps->tomador->endereco->cep ?? '', '40020010');

        $enderDest = $dom->createElement('enderDest');
        $enderDest->appendChild($this->createElementWithText($dom, 'xLgr', $logradouro));
        $enderDest->appendChild($this->createElementWithText($dom, 'nro', $numero));
        $enderDest->appendChild($this->createElementWithText($dom, 'xBairro', $bairro));

        // Use code if available, otherwise default to Salvador
        $enderDest->appendChild($dom->createElement('cMun', $rps->tomador->endereco->codigoMunicipio ?: '2927408'));

        // Use mapped city name if available, otherwise default to Salvador
        $xMun = $this->getValidValue($rps->tomador->endereco->xMun ?? '', 'SALVADOR');
        $enderDest->appendChild($dom->createElement('xMun', strtoupper($xMun)));

        $enderDest->appendChild($dom->createElement('UF', $rps->tomador->endereco->uf ?: 'BA'));
        $enderDest->appendChild($dom->createElement('CEP', preg_replace('/\D/', '', $cep)));
        $enderDest->appendChild($dom->createElement('cPais', '1058'));
        $enderDest->appendChild($dom->createElement('xPais', 'Brasil'));
        $dest->appendChild($enderDest);

        // 9 = Não Contribuinte, que pode ou não possuir Inscrição Estadual no Cadastro de Contribuintes do ICMS
        // Mais seguro quando não temos a informação da IE
        $dest->appendChild($dom->createElement('indIEDest', '9'));

        return $dest;
    }

    private function createDet(DOMDocument $dom, $rps, string $providerState): DOMElement
    {
        $det = $dom->createElement('det');
        $det->setAttribute('nItem', '1');

        $prod = $dom->createElement('prod');
        $det->appendChild($prod);

        // Avoid using '1' as cProd to prevent conflict with existing Domínio products
        // Use a generic code that likely doesn't exist or is specific to this import
        $prod->appendChild($dom->createElement('cProd', 'ALUGUEL_01'));
        $prod->appendChild($dom->createElement('cEAN', 'SEM GTIN'));
        $prod->appendChild($dom->createElement('xProd', 'RECEBIMENTO DE ALUGUEIS')); // More specific description
        $prod->appendChild($dom->createElement('NCM', '00')); // '00' is often accepted for services/rents where NCM doesn't apply

        // CFOP Logic: 5949 (Intra) / 6949 (Inter) based on UF
        $tomadorUF = $rps->tomador->endereco->uf ?? 'BA';
        $cfop = ($providerState === $tomadorUF) ? '5949' : '6949';
        $prod->appendChild($dom->createElement('CFOP', $cfop));

        $prod->appendChild($dom->createElement('uCom', 'UN'));
        $prod->appendChild($dom->createElement('qCom', '1.0000'));

        $valor = number_format($rps->servico->valorServico, 2, '.', '');
        $prod->appendChild($dom->createElement('vUnCom', $valor));
        $prod->appendChild($dom->createElement('vProd', $valor));

        $prod->appendChild($dom->createElement('cEANTrib', 'SEM GTIN'));
        $prod->appendChild($dom->createElement('uTrib', 'UN'));
        $prod->appendChild($dom->createElement('qTrib', '1.0000'));
        $prod->appendChild($dom->createElement('vUnTrib', $valor));

        $prod->appendChild($dom->createElement('indTot', '1'));

        // Imposto - Tax Information
        $imposto = $dom->createElement('imposto');
        $det->appendChild($imposto);

        // ICMS - Regime Normal (CST 00 - Tributada integralmente)
        // Use CST instead of CSOSN if company is NOT Simples Nacional
        $icms = $dom->createElement('ICMS');
        $imposto->appendChild($icms);

        $icms00 = $dom->createElement('ICMS00');
        $icms->appendChild($icms00);
        $icms00->appendChild($dom->createElement('orig', '0')); // 0-Nacional
        $icms00->appendChild($dom->createElement('CST', '00')); // 00-Tributada integralmente
        $icms00->appendChild($dom->createElement('modBC', '3')); // 3-Valor da operação
        $icms00->appendChild($dom->createElement('vBC', '0.00'));
        $icms00->appendChild($dom->createElement('pICMS', '0.00'));
        $icms00->appendChild($dom->createElement('vICMS', '0.00'));

        // PIS - Operação Tributável (CST 01 - Base de Cálculo = Valor da Operação)
        $pis = $dom->createElement('PIS');
        $imposto->appendChild($pis);

        $pisAliq = $dom->createElement('PISAliq');
        $pis->appendChild($pisAliq);
        $pisAliq->appendChild($dom->createElement('CST', '01')); // 01-Operação Tributável
        $pisAliq->appendChild($dom->createElement('vBC', $valor));
        $pisAliq->appendChild($dom->createElement('pPIS', '0.65')); // 0.65% (user's PDF shows this rate)
        $pisValue = number_format($rps->servico->valorServico * 0.0065, 2, '.', '');
        $pisAliq->appendChild($dom->createElement('vPIS', $pisValue));

        // COFINS - Operação Tributável (CST 01 - Base de Cálculo = Valor da Operação)
        $cofins = $dom->createElement('COFINS');
        $imposto->appendChild($cofins);

        $cofinsAliq = $dom->createElement('COFINSAliq');
        $cofins->appendChild($cofinsAliq);
        $cofinsAliq->appendChild($dom->createElement('CST', '01')); // 01-Operação Tributável
        $cofinsAliq->appendChild($dom->createElement('vBC', $valor));
        $cofinsAliq->appendChild($dom->createElement('pCOFINS', '3.00')); // 3.00% (user's PDF shows this rate)
        $cofinsValue = number_format($rps->servico->valorServico * 0.03, 2, '.', '');
        $cofinsAliq->appendChild($dom->createElement('vCOFINS', $cofinsValue));

        return $det;
    }

    private function createTotal(DOMDocument $dom, $rps): DOMElement
    {
        $total = $dom->createElement('total');
        $icmsTot = $dom->createElement('ICMSTot');

        $valor = number_format($rps->servico->valorServico, 2, '.', '');

        // Calculate PIS and COFINS based on rates from user's PDF
        $pisValue = number_format($rps->servico->valorServico * 0.0065, 2, '.', ''); // 0.65%
        $cofinsValue = number_format($rps->servico->valorServico * 0.03, 2, '.', ''); // 3.00%

        $icmsTot->appendChild($dom->createElement('vBC', '0.00'));
        $icmsTot->appendChild($dom->createElement('vICMS', '0.00'));
        $icmsTot->appendChild($dom->createElement('vICMSDeson', '0.00'));
        $icmsTot->appendChild($dom->createElement('vFCP', '0.00'));
        $icmsTot->appendChild($dom->createElement('vBCST', '0.00'));
        $icmsTot->appendChild($dom->createElement('vST', '0.00'));
        $icmsTot->appendChild($dom->createElement('vFCPST', '0.00'));
        $icmsTot->appendChild($dom->createElement('vFCPSTRet', '0.00'));
        $icmsTot->appendChild($dom->createElement('vProd', $valor));
        $icmsTot->appendChild($dom->createElement('vFrete', '0.00'));
        $icmsTot->appendChild($dom->createElement('vSeg', '0.00'));
        $icmsTot->appendChild($dom->createElement('vDesc', '0.00'));
        $icmsTot->appendChild($dom->createElement('vII', '0.00'));
        $icmsTot->appendChild($dom->createElement('vIPI', '0.00'));
        $icmsTot->appendChild($dom->createElement('vIPIDevol', '0.00'));
        $icmsTot->appendChild($dom->createElement('vPIS', $pisValue));
        $icmsTot->appendChild($dom->createElement('vCOFINS', $cofinsValue));
        $icmsTot->appendChild($dom->createElement('vOutro', '0.00'));
        $icmsTot->appendChild($dom->createElement('vNF', $valor));

        $total->appendChild($icmsTot);

        return $total;
    }

    private function createTransp(DOMDocument $dom): DOMElement
    {
        $transp = $dom->createElement('transp');
        $transp->appendChild($dom->createElement('modFrete', '9')); // 9=Sem frete
        return $transp;
    }

    private function createPag(DOMDocument $dom, $rps): DOMElement
    {
        $pag = $dom->createElement('pag');
        $detPag = $dom->createElement('detPag');

        $detPag->appendChild($dom->createElement('indPag', '0')); // 0=Pagamento à vista
        $detPag->appendChild($dom->createElement('tPag', '99')); // 99=Outros

        $valor = number_format($rps->servico->valorServico, 2, '.', '');
        $detPag->appendChild($dom->createElement('vPag', $valor));

        $pag->appendChild($detPag);


        return $pag;
    }

    private function createInfAdic(DOMDocument $dom, string $batchId = ''): DOMElement
    {
        $infAdic = $dom->createElement('infAdic');
        $tag = 'IMPORTACAO MANUAL - NAO LANCAR AUTOMATICAMENTE';
        if ($batchId) {
            $tag .= ' | REF ID: ' . $batchId;
        }

        $infAdic->appendChild($this->createElementWithText($dom, 'infCpl', $tag));

        return $infAdic;
    }

    /**
     * Remove accents and make uppercase for better compatibility with accounting systems
     */
    private function sanitizeText(string $text): string
    {
        $text = mb_strtoupper(trim($text));
        $map = [
            'Á' => 'A',
            'À' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'A',
            'É' => 'E',
            'È' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Í' => 'I',
            'Ì' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ó' => 'O',
            'Ò' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ö' => 'O',
            'Ú' => 'U',
            'Ù' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Ç' => 'C',
            'Ñ' => 'N'
        ];
        return strtr($text, $map);
    }

    /**
     * Checks if a value is actually useful (not empty and not a placeholder like 'NAO INFORMADO')
     */
    private function getValidValue(?string $value, string $fallback): string
    {
        $value = trim($value ?? '');
        $placeholders = ['NAO INFORMADO', 'NÃO INFORMADO', 'NAO CONSTA', 'N/A', '.', '-', 'SEM INFORMACAO', 'Vazio'];

        if (empty($value) || in_array(mb_strtoupper($value), $placeholders)) {
            return $fallback;
        }
        return mb_strtoupper($value);
    }

    /**
     * Parse date from various formats and return useful parts
     */
    private function parseFlexibleDate($date): array
    {
        if (!$date)
            return [];

        $dateStr = trim((string) $date);
        if (empty($dateStr))
            return [];

        $dt = null;

        // Try ISO Y-m-d (from our new MappingService normalization)
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $dateStr)) {
            $dt = \DateTime::createFromFormat('Y-m-d', substr($dateStr, 0, 10));
        }
        // Try d/m/Y (Historical standard)
        elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}/', $dateStr)) {
            $dt = \DateTime::createFromFormat('d/m/Y', substr($dateStr, 0, 10));
        }

        // Final fallback: strtotime
        $errors = \DateTime::getLastErrors();
        $hasErrors = $errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

        if (!$dt || $hasErrors) {
            $ts = strtotime($dateStr);
            if ($ts) {
                $dt = new \DateTime("@$ts");
                // Set timezone since @timestamp is UTC
                $dt->setTimezone(new \DateTimeZone('America/Bahia'));
            }
        }

        if ($dt) {
            return [
                'ym' => $dt->format('ym'),
                'iso' => $dt->format('Y-m-d'),
                'iso_tz' => $dt->format('Y-m-d') . 'T12:00:00-03:00'
            ];
        }

        \Log::warning("XmlGeneratorNFeService: Failed to parse date: " . $dateStr);
        return [];
    }

    /**
     * Get the IBGE code for a given UF (State abbreviation)
     */
    private function getUFCode(string $uf): string
    {
        $codes = [
            'AC' => '12',
            'AL' => '27',
            'AP' => '16',
            'AM' => '13',
            'BA' => '29',
            'CE' => '23',
            'DF' => '53',
            'ES' => '32',
            'GO' => '52',
            'MA' => '21',
            'MT' => '51',
            'MS' => '50',
            'MG' => '31',
            'PA' => '15',
            'PB' => '25',
            'PR' => '41',
            'PE' => '26',
            'PI' => '22',
            'RJ' => '33',
            'RN' => '24',
            'RS' => '43',
            'RO' => '11',
            'RR' => '14',
            'SC' => '42',
            'SP' => '35',
            'SE' => '28',
            'TO' => '17'
        ];

        return $codes[strtoupper($uf)] ?? '29'; // Default to BA (29)
    }
}
