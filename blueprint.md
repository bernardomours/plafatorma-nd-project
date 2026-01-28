# Visão Geral do Projeto

Este é um projeto Laravel para uma plataforma de gerenciamento de uma clínica.

## Funcionalidades

*   Gerenciamento de pacientes
*   Gerenciamento de profissionais
*   Gerenciamento de unidades
*   Gerenciamento de terapias
*   Gerenciamento de serviços solicitados
*   Gerenciamento de agendamentos
*   Gerenciamento de horários

## Design e Estilo

*   **Framework:** Filament (para o painel administrativo)
*   **Cores:** Paleta de cores padrão do Filament
*   **Tipografia:** Padrão do Filament

## Plano de Alterações Recentes

### Correção de Erro Crítico e Refatoração do Formulário de Criação

*   **Objetivo:** Corrigir o erro fatal `Class "Filament\Forms\Form" not found` na página de serviços do paciente e modernizar a experiência de filtragem.

*   **Passos:**
    1.  **Diagnóstico:** O erro foi causado por uma incompatibilidade na forma como a `CreateAction` tentava consumir uma classe de `Schema` personalizada (`RequestedServiceForm`). A abordagem estava desalinhada com a maneira como o Filament gerencia formulários em ações.

    2.  **Refatoração do `RequestedServiceForm.php`:**
        *   A classe foi reescrita para remover a dependência da classe `Schema`.
        *   Foi criado um método estático `getFormSchema()` que retorna um *array* de componentes de formulário do Filament, conforme as práticas recomendadas.
        *   O campo de seleção de paciente foi removido do formulário, pois o paciente já é pré-determinado pelo contexto da página.

    3.  **Correção do `PatientServices.php`:**
        *   A `CreateAction` foi atualizada para chamar o novo método `RequestedServiceForm::getFormSchema()`, resolvendo o erro.
        *   A lógica de filtragem foi ajustada para lidar com um valor de filtro vazio, melhorando a robustez.

    4.  **Melhoria da Interface do Utilizador (`patient-services.blade.php`):**
        *   O `wire:model` do campo de filtro foi alterado para `wire:model.live`, implementando uma filtragem automática e reativa que atualiza a tabela instantaneamente à medida que o utilizador seleciona o mês/ano.
        *   O botão "Filtrar", que se tornou redundante, foi removido, simplificando a interface.
