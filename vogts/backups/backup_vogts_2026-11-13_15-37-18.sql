-- BACKUP AUTOMÁTICO VOGTS - DATA: 13/11/2026 15:37:18

-- Dados da tabela: usuarios
INSERT INTO usuarios (id, nome, login, senha, perfil) VALUES ('1', 'Administrador Vogts', 'gerente', '$2y$10$3Gj4vSjNpnlbwlqE.6SvhOyfYqdnkLnRvnZKzKMazG3dOq2UcXG5a', 'gerente');
INSERT INTO usuarios (id, nome, login, senha, perfil) VALUES ('2', 'Vendedor Loja', 'vendedor', '$2y$10$3Gj4vSjNpnlbwlqE.6SvhOyfYqdnkLnRvnZKzKMazG3dOq2UcXG5a', 'vendedor');

-- Dados da tabela: produtos
INSERT INTO produtos (id, codigo, nome, categoria, tamanho, cor, preco, estoque) VALUES ('1', 'fgr-174', 'camiseta preta', 'Camisetas', 'G', 'preto', '90.00', '3');
INSERT INTO produtos (id, codigo, nome, categoria, tamanho, cor, preco, estoque) VALUES ('2', 'hkl-135', 'calça jeans', 'Calças', 'M', 'azul', '125.99', '6');

-- Dados da tabela: clientes
INSERT INTO clientes (id, nome, cpf, telefone, email, senha) VALUES ('1', 'alexandre', NULL, '42999859495', 'alexandre.wogt@escola.pr.gov.br', '$2y$10$oAuMauogaqxP4gj7DqDu5eI2kL1MJKVcCkgfmfTFiKvZA3vBV4.fq');

-- Dados da tabela: vendas

-- Dados da tabela: itens_venda

-- Dados da tabela: devolucoes_creditos

