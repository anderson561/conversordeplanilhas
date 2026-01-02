<x-mail::message>
    # Olá, {{ $conversionJob->upload->user->name }}!

    Ótimas notícias! O processamento do seu arquivo **{{ $conversionJob->upload->original_name }}** foi concluído com
    sucesso.

    Você já pode baixar os seus arquivos convertidos no padrão ABRASF/Domínio Sistemas diretamente no seu painel.

    <x-mail::button :url="config('app.url') . '/uploads'">
        Ver Meus Arquivos
    </x-mail::button>

    Obrigado por usar o XML Converter!<br>
    {{ config('app.name') }}
</x-mail::message>