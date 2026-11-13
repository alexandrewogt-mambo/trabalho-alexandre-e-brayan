-- BACKUP AUTOMÁTICO VOGTS - DATA: 13/11/2026 15:44:58

-- Dados da tabela: usuarios
INSERT INTO usuarios (id, nome, login, senha, perfil) VALUES ('1', 'Administrador Vogts', 'gerente', '$2y$10$3Gj4vSjNpnlbwlqE.6SvhOyfYqdnkLnRvnZKzKMazG3dOq2UcXG5a', 'gerente');
INSERT INTO usuarios (id, nome, login, senha, perfil) VALUES ('2', 'Vendedor Loja', 'vendedor', '$2y$10$3Gj4vSjNpnlbwlqE.6SvhOyfYqdnkLnRvnZKzKMazG3dOq2UcXG5a', 'vendedor');

-- Dados da tabela: produtos
INSERT INTO produtos (id, codigo, nome, categoria, tamanho, cor, preco, estoque) VALUES ('1', 'fgr-174', 'camiseta preta', 'Camisetas', 'G', 'preto', '90.00', '2');
INSERT INTO produtos (id, codigo, nome, categoria, tamanho, cor, preco, estoque) VALUES ('2', 'hkl-135', 'calça jeans', 'Calças', 'M', 'azul', '125.99', '5');

-- Dados da tabela: clientes
INSERT INTO clientes (id, nome, cpf, telefone, email, senha) VALUES ('1', 'alexandre', NULL, '42999859495', 'alexandre.wogt@escola.pr.gov.br', '$2y$10$oAuMauogaqxP4gj7DqDu5eI2kL1MJKVcCkgfmfTFiKvZA3vBV4.fq');

-- Dados da tabela: vendas
INSERT INTO vendas (id, cliente_id, usuario_id, cpf_nota, valor_bruto, desconto, valor_final, forma_pagamento, status, data_venda) VALUES ('1', '1', '1', NULL, '215.99', '0.00', '215.99', 'pix', 'concluida', '2026-11-13 11:38:48');

-- Dados da tabela: itens_venda
INSERT INTO itens_venda (id, venda_id, produto_id, quantidade, preco_unitario) VALUES ('1', '1', '1', '1', '90.00');
INSERT INTO itens_venda (id, venda_id, produto_id, quantidade, preco_unitario) VALUES ('2', '1', '2', '1', '125.99');

-- Dados da tabela: devolucoes_creditos

