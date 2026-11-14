# HST & AST Report

O HST e os ASTs de cada crônica publicada, com os emails pessoais deles, para conferir as fichas da staff e falar com os narradores.

## Quem pode usar

Os administradores do site, a equipe executiva e a equipe de Web, em chronicles.owbn.net.

## Como chegar

wp-admin em chronicles.owbn.net → **OWBN C&C** → **HST & AST Report**.

## A tela

- "N active chronicles. N have an HST or AST with no name or no email address."
- **Filter by chronicle, name or email** - filtra a tabela enquanto você digita.
- **Only chronicles with a missing name or email** - mostra só essas.
- **Export CSV** - o relatório inteiro como arquivo de planilha.
- Uma linha por crônica publicada, por nome:
  - **Chronicle** - o nome, que a abre, e o slug.
  - **HST (email)** - o nome e o email pessoal do HST.
  - **Staff (emails)** - o nome e o email pessoal de cada AST, ou "None listed".
  - **Last Updated** - o dia em que a ficha da crônica mudou pela última vez.
- "No name" e "No email" marcam o que falta.
- Clique no título de uma coluna para ordenar por ela.

## Tarefas comuns

### Achar crônicas sem nome ou email

1. Marque **Only chronicles with a missing name or email**.
2. Clique no nome de uma crônica e preencha o que falta na aba **Game, Schedule & Staff** dela.

### Ter a lista como planilha

1. Clique em **Export CSV**. O arquivo, `owbn-hst-ast-<data>.csv`, tem as mesmas quatro colunas, com cada pessoa escrita como "Nome <email>".

## Bom saber

- Os emails são os endereços pessoais da staff, o **Actual Email**, não os públicos. Mantenha o relatório e o CSV entre a staff.
- Crônicas em rascunho e desativadas não entram.
- Os CMs não entram. **Reports** → **Chronicle Staff**, do OWBN Core, os lista.

## Solução de problemas

- **Sem o menu HST & AST Report.** Ele é para os administradores do site, a equipe executiva e a equipe de Web, no site que guarda as crônicas.
- **Alguém aparece com "No email", mas a página da crônica mostra um.** A página mostra o **Display Email**; o relatório lê o **Actual Email**. Preencha-o na crônica.

## Relacionados

- [Crônicas](chronicles.md)
- [Editar uma crônica](chronicle-edit.md)
