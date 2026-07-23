import { Contract, parseEther } from 'ethers';
import api from '../api';
import { connectMetaMask, signMessage, ensureHardhatNetwork } from './web3';
import { getConfig as getBlockchainConfig } from './contract';

const REGISTRY_ABI = [
    'function initiateSale(bytes32 vehicleUuidHash, address buyerWallet, uint256 salePrice)',
    'function signSaleAsBuyer(bytes32 vehicleUuidHash)',
];

export async function prepareSale(vehicleId, buyerWallet, salePrice) {
    const { data } = await api.post('/blockchain/sales/prepare', {
        vehicle_id: vehicleId,
        buyer_wallet: buyerWallet,
        sale_price: salePrice,
    });
    return data;
}

export async function initiateSaleWithMetaMask(vehicleId, buyerWallet, salePrice) {
    const prepared = await prepareSale(vehicleId, buyerWallet, salePrice);
    const config = await getBlockchainConfig();

    if (config.enabled && config.chain_id) {
        await ensureHardhatNetwork(config.chain_id);
    }

    const { signer, address } = await connectMetaMask();

    const adminSignature = await signMessage(signer, prepared.admin_message);

    const { data } = await api.post('/blockchain/sales/initiate', {
        vehicle_id: vehicleId,
        buyer_wallet: buyerWallet,
        sale_price: salePrice,
        admin_signature: adminSignature,
    });

    let onChainTx = null;

    if (config.enabled && config.contract_address && config.contract_abi?.length) {
        try {
            onChainTx = await initiateSaleOnChain(
                prepared.vehicle_uuid_hash,
                buyerWallet,
                salePrice,
                config.contract_address,
                config.contract_abi.length ? config.contract_abi : REGISTRY_ABI,
                signer
            );
        } catch (e) {
            console.warn('On-chain initiateSale:', e.message);
        }
    }

    return { ...data, admin_wallet: address, on_chain_tx: onChainTx };
}

export async function signSaleWithMetaMask(pendingId) {
    const { data: saleData } = await api.get(`/blockchain/sales/${pendingId}`);
    const pending = saleData.pending_signature;
    const buyerMessage = saleData.buyer_message;

    const config = await getBlockchainConfig();

    if (config.enabled && config.chain_id) {
        await ensureHardhatNetwork(config.chain_id);
    }

    const { signer, address } = await connectMetaMask();

    if (address !== pending.buyer_wallet?.toLowerCase()) {
        throw new Error(`Connectez le wallet acheteur : ${pending.buyer_wallet}`);
    }

    const buyerSignature = await signMessage(signer, buyerMessage);

    let onChainTx = null;
    const uuidHash = saleData.vehicle_uuid_hash;

    if (config.enabled && config.contract_address && uuidHash) {
        try {
            onChainTx = await signSaleOnChain(
                uuidHash,
                config.contract_address,
                config.contract_abi?.length ? config.contract_abi : REGISTRY_ABI,
                signer
            );
        } catch (e) {
            console.warn('On-chain signSaleAsBuyer:', e.message);
        }
    }

    const { data } = await api.post(`/blockchain/sales/${pendingId}/sign`, {
        buyer_signature: buyerSignature,
        on_chain_tx_hash: onChainTx,
    });

    return { ...data, buyer_wallet: address, on_chain_tx: onChainTx };
}

async function initiateSaleOnChain(uuidHashHex, buyerWallet, salePrice, contractAddress, abi, signer) {
    const contract = new Contract(contractAddress, abi, signer);
    const hashBytes32 = uuidHashHex.startsWith('0x') ? uuidHashHex : `0x${uuidHashHex}`;
    const tx = await contract.initiateSale(hashBytes32, buyerWallet, parseEther(String(salePrice)));
    const receipt = await tx.wait();
    return receipt.hash;
}

async function signSaleOnChain(uuidHashHex, contractAddress, abi, signer) {
    const contract = new Contract(contractAddress, abi, signer);
    const hashBytes32 = uuidHashHex.startsWith('0x') ? uuidHashHex : `0x${uuidHashHex}`;
    const tx = await contract.signSaleAsBuyer(hashBytes32);
    const receipt = await tx.wait();
    return receipt.hash;
}

export async function fetchPendingSales(params = {}) {
    const { data } = await api.get('/blockchain/sales/pending', { params });
    return data.data ?? data;
}
