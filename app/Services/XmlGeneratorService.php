<?php

namespace App\Services;

use App\Contracts\OutputGeneratorInterface;
use App\DTOs\RpsData;
use DOMDocument;

class XmlGeneratorService implements OutputGeneratorInterface
{
    public function generateBatch(array $rpsList, string $loteId = '1', array $providerInfo = [], array $options = []): string
    {
        return $this->generateBatchXml($rpsList, $loteId, $providerInfo);
    }

    public function getExtension(): string
    {
        return 'xml';
    }

    public function generateBatchXml(array $rpsList, string $loteId = '1', array $providerInfo = []): string
    {
        $dom = new DOMDocument('1.0', 'ISO-8859-1'); // Adjusted encoding to match user sample usually
        $dom->formatOutput = true;

        // Default provider info if not provided
        $cnpj_raw = $providerInfo['cnpj'] ?? '00000000000000';
        $cnpj = preg_replace('/\D/', '', $cnpj_raw);

        $im_raw = $providerInfo['inscricao_municipal'] ?? '000000';
        $inscricaoMunicipal = preg_replace('/\D/', '', $im_raw);

        // ROOT: ConsultarNfseResposta (Matches XML B / Domínio Expectation)
        $root = $dom->createElement('ConsultarNfseResposta');
        $root->setAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $root->setAttribute('xmlns', 'http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd');
        $dom->appendChild($root);

        // LIST: ListaNfse (Matches XML B)
        $listaNfse = $dom->createElement('ListaNfse');
        $root->appendChild($listaNfse);

        foreach ($rpsList as $index => $rpsData) {
            /** @var RpsData $rpsData */

            // WRAPPER: CompNfse (Matches XML B)
            $compNfse = $dom->createElement('CompNfse');
            $listaNfse->appendChild($compNfse);

            // NOTE: Nfse
            $nfse = $dom->createElement('Nfse');
            $compNfse->appendChild($nfse);

            // INFO: InfNfse (Replaces InfRps)
            $infNfse = $dom->createElement('InfNfse');
            // $infNfse->setAttribute('Id', 'nfse' . ($index + 1)); // Domínio often ignores ID or it helps validation. Keeping minimal.
            $nfse->appendChild($infNfse);

            // 1. Numero (Required by Schema in InfNfse, mapped from RPS or generated)
            $infNfse->appendChild($dom->createElement('Numero', $rpsData->numero));

            // 2. CodigoVerificacao (Fake generator for valid XML structure)
            $codVerif = strtoupper(substr(md5($rpsData->numero . $rpsData->dataEmissao), 0, 8));
            $infNfse->appendChild($dom->createElement('CodigoVerificacao', $codVerif));

            // 3. DataEmissao
            // Format Date as Y-m-d\TH:i:sP (with timezone, matching Salvador User Sample)
            $dtEmissaoObj = \DateTime::createFromFormat('d/m/Y', $rpsData->dataEmissao);
            $dtEmissaoFormatted = $dtEmissaoObj ? $dtEmissaoObj->format('Y-m-d\TH:i:sP') : date('Y-m-d\TH:i:sP');
            $infNfse->appendChild($dom->createElement('DataEmissao', $dtEmissaoFormatted));

            // 4. IdentificacaoRps (Optional in InfNfse but good for tracking)
            $identificacaoRps = $dom->createElement('IdentificacaoRps');
            $infNfse->appendChild($identificacaoRps);
            $identificacaoRps->appendChild($dom->createElement('Numero', $rpsData->numero));
            $identificacaoRps->appendChild($dom->createElement('Serie', $rpsData->serie));
            $identificacaoRps->appendChild($dom->createElement('Tipo', $rpsData->tipo));

            // 5. NaturezaOperacao
            $infNfse->appendChild($dom->createElement('NaturezaOperacao', '1')); // 1 - Tributação no município

            // 6. OptanteSimplesNacional
            $infNfse->appendChild($dom->createElement('OptanteSimplesNacional', '1')); // 1-Sim (User sample has 1)

            // 7. Competencia
            // User Sample has Competencia: 2025-11-01T00:00:00-03:00
            $competenciaObj = $dtEmissaoObj ? $dtEmissaoObj->modify('first day of this month') : new \DateTime();
            $competenciaFormatted = $competenciaObj->format('Y-m-d\TH:i:sP');
            $infNfse->appendChild($dom->createElement('Competencia', $competenciaFormatted));

            $infNfse->appendChild($dom->createElement('NfseSubstituida', '0'));

            // Add Status to prevent automatic posting to ICMS segment
            $infNfse->appendChild($dom->createElement('Status', 'Rascunho'));

            $infNfse->appendChild($dom->createElement('OutrasInformacoes', 'IMPORTACAO MANUAL - NAO LANCAR AUTOMATICAMENTE'));

            // 8. Servico
            $servico = $dom->createElement('Servico');
            $infNfse->appendChild($servico);

            $valores = $dom->createElement('Valores');
            $servico->appendChild($valores);

            // Helper function to format decimal values properly
            // Use COMMA as separator (Salvador User Sample uses 16,50), return '0,00' if value is 0
            $formatDecimal = function ($value, $decimals = 2) {
                $num = floatval($value ?? 0);
                if ($num == 0)
                    return '0,' . str_repeat('0', $decimals);
                return number_format($num, $decimals, ',', '');
            };

            $valores->appendChild($dom->createElement('ValorServicos', $formatDecimal($rpsData->servico->valorServico, 2)));
            $valores->appendChild($dom->createElement('ValorDeducoes', $formatDecimal($rpsData->servico->valorDeducoes, 2)));
            $valores->appendChild($dom->createElement('ValorPis', $formatDecimal($rpsData->servico->valorPis, 2)));
            $valores->appendChild($dom->createElement('ValorCofins', $formatDecimal($rpsData->servico->valorCofins, 2)));
            $valores->appendChild($dom->createElement('ValorInss', $formatDecimal($rpsData->servico->valorInss, 2)));
            $valores->appendChild($dom->createElement('ValorIr', $formatDecimal($rpsData->servico->valorIr, 2)));
            $valores->appendChild($dom->createElement('ValorCsll', $formatDecimal($rpsData->servico->valorCsll, 2)));
            $valores->appendChild($dom->createElement('IssRetido', $rpsData->servico->issRetido ?? 2));
            $valores->appendChild($dom->createElement('ValorIss', $formatDecimal($rpsData->servico->valorIss, 2)));
            $valores->appendChild($dom->createElement('OutrasRetencoes', $formatDecimal($rpsData->servico->outrasRetencoes, 2)));
            $valores->appendChild($dom->createElement('BaseCalculo', $formatDecimal($rpsData->servico->baseCalculo ?? $rpsData->servico->valorServico, 2)));
            $valores->appendChild($dom->createElement('Aliquota', $formatDecimal($rpsData->servico->aliquota, 2))); // User sample has 0,05
            $valores->appendChild($dom->createElement('ValorLiquidoNfse', $formatDecimal($rpsData->servico->valorLiquidoNfse ?? $rpsData->servico->valorServico, 2)));
            $valores->appendChild($dom->createElement('DescontoIncondicionado', $formatDecimal($rpsData->servico->descontoIncondicionado, 2)));
            $valores->appendChild($dom->createElement('DescontoCondicionado', $formatDecimal($rpsData->servico->descontoCondicionado, 2)));

            // Helper to create element with text content safely (escapes XML special chars)
            $createElementWithText = function ($tagName, $textContent) use ($dom) {
                $element = $dom->createElement($tagName);
                if ($textContent !== null && $textContent !== '') {
                    $textContent = preg_replace('/[^\x20-\x7E]/', '', $textContent); // Basic stripping of weird chars
                    $element->appendChild($dom->createTextNode($textContent));
                }
                return $element;
            };

            $servico->appendChild($dom->createElement('ItemListaServico', $rpsData->servico->itemListaServico));
            $servico->appendChild($dom->createElement('CodigoCnae', $rpsData->servico->codigoCnae ?? ''));
            $servico->appendChild($dom->createElement('CodigoTributacaoMunicipio', $rpsData->servico->codigoTributacaoMunicipio ?? ''));
            $servico->appendChild($createElementWithText('Discriminacao', $rpsData->servico->discriminacao));
            $servico->appendChild($dom->createElement('CodigoMunicipio', $rpsData->servico->codigoMunicipio));

            // 9. PrestadorServico
            $prestador = $dom->createElement('PrestadorServico');
            $infNfse->appendChild($prestador);

            $identificacaoPrestador = $dom->createElement('IdentificacaoPrestador');
            $prestador->appendChild($identificacaoPrestador);
            $identificacaoPrestador->appendChild($dom->createElement('Cnpj', $cnpj)); // Use dynamic CNPJ
            $identificacaoPrestador->appendChild($dom->createElement('InscricaoMunicipal', $inscricaoMunicipal)); // Use dynamic IM

            $prestador->appendChild($createElementWithText('RazaoSocial', $providerInfo['razao_social'] ?? 'PRESTADOR'));

            $endPrest = $dom->createElement('Endereco');
            $prestador->appendChild($endPrest);
            $endPrest->appendChild($dom->createElement('Endereco', $providerInfo['endereco'] ?? ''));
            $endPrest->appendChild($dom->createElement('Numero', ''));
            $endPrest->appendChild($dom->createElement('Complemento', ''));
            $endPrest->appendChild($dom->createElement('Bairro', $providerInfo['bairro'] ?? ''));
            $endPrest->appendChild($dom->createElement('CodigoMunicipio', '2927408'));
            $endPrest->appendChild($dom->createElement('Uf', $providerInfo['uf'] ?? 'BA'));
            $endPrest->appendChild($dom->createElement('Cep', preg_replace('/\D/', '', $providerInfo['cep'] ?? '')));

            $contatoPrest = $dom->createElement('Contato');
            $prestador->appendChild($contatoPrest);
            $contatoPrest->appendChild($dom->createElement('Telefone', preg_replace('/\D/', '', $providerInfo['fone'] ?? '')));
            $contatoPrest->appendChild($dom->createElement('Email', ''));


            // 10. TomadorServico
            $tomador = $dom->createElement('TomadorServico');
            $infNfse->appendChild($tomador);

            $identificacaoTomador = $dom->createElement('IdentificacaoTomador');
            $tomador->appendChild($identificacaoTomador);

            $cpfCnpjTomador = $dom->createElement('CpfCnpj');
            $identificacaoTomador->appendChild($cpfCnpjTomador);

            if (strlen($rpsData->tomador->cpfCnpj) == 11) {
                $cpfCnpjTomador->appendChild($dom->createElement('Cpf', $rpsData->tomador->cpfCnpj));
            } else {
                $cpfCnpjTomador->appendChild($dom->createElement('Cnpj', $rpsData->tomador->cpfCnpj));
            }

            if ($rpsData->tomador->inscricaoMunicipal) {
                $identificacaoTomador->appendChild($dom->createElement('InscricaoMunicipal', $rpsData->tomador->inscricaoMunicipal));
            }

            // Sanitize RazaoSocial
            $razaoSocialTomador = mb_strtoupper(trim($rpsData->tomador->razaoSocial));
            $razaoSocialTomador = preg_replace('/^[\d\.,\s]+/', '', $razaoSocialTomador);
            if (strlen($razaoSocialTomador) > 115) {
                $razaoSocialTomador = substr($razaoSocialTomador, 0, 115);
            }

            $tomador->appendChild($createElementWithText('RazaoSocial', $razaoSocialTomador));

            if ($rpsData->tomador->endereco) {
                $endereco = $dom->createElement('Endereco');
                $tomador->appendChild($endereco);
                $endereco->appendChild($dom->createElement('Endereco', $rpsData->tomador->endereco->logradouro));
                $endereco->appendChild($dom->createElement('Numero', $rpsData->tomador->endereco->numero));
                $endereco->appendChild($dom->createElement('Complemento', $rpsData->tomador->endereco->complemento ?? ''));
                $endereco->appendChild($dom->createElement('Bairro', $rpsData->tomador->endereco->bairro));
                $endereco->appendChild($dom->createElement('CodigoMunicipio', $rpsData->tomador->endereco->codigoMunicipio));
                $endereco->appendChild($dom->createElement('Uf', $rpsData->tomador->endereco->uf));
                $endereco->appendChild($dom->createElement('Cep', $rpsData->tomador->endereco->cep));
            }

            $contatoTomador = $dom->createElement('Contato');
            $tomador->appendChild($contatoTomador);
            $contatoTomador->appendChild($dom->createElement('Telefone', ''));
            $contatoTomador->appendChild($dom->createElement('Email', ''));

            // 11. OrgaoGerador
            $orgao = $dom->createElement('OrgaoGerador');
            $infNfse->appendChild($orgao);
            $orgao->appendChild($dom->createElement('CodigoMunicipio', '2927408'));
            $orgao->appendChild($dom->createElement('Uf', 'BA'));

            // 12. ConstrucaoCivil
            $construcao = $dom->createElement('ContrucaoCivil'); // Note: Some schemas use ConstrucaoCivil with 's', keeping 'Contrucao' if that's standard for ABRASF, but typically it is 'Construcao'. Checking snippet: typically 'ConstrucaoCivil'. I will use 'ConstrucaoCivil'.
            // Actually ABRASF 1.0 is ConstrucaoCivil.
            // Let's stick to standard spelling unless proven otherwise.
            $construcao = $dom->createElement('ConstrucaoCivil');
            $infNfse->appendChild($construcao);
            $construcao->appendChild($dom->createElement('CodigoObra', ''));
            $construcao->appendChild($dom->createElement('Art', ''));
        }

        return $dom->saveXML();
    }
}
