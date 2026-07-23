import { BrowserProvider } from 'ethers';

export const HARDHAT_CHAIN_ID = 31337;

export const HARDHAT_NETWORK = {
    chainId: '0x7A69',
    chainName: 'Hardhat Local (AutoChain)',
    nativeCurrency: { name: 'ETH', symbol: 'ETH', decimals: 18 },
    rpcUrls: ['http://127.0.0.1:8545'],
};

export function hasMetaMask() {
    return typeof window !== 'undefined' && !!window.ethereum;
}

export async function ensureHardhatNetwork(expectedChainId = HARDHAT_CHAIN_ID) {
    if (!hasMetaMask()) return;

    const provider = new BrowserProvider(window.ethereum);
    const network = await provider.getNetwork();

    if (Number(network.chainId) === expectedChainId) return;

    try {
        await window.ethereum.request({
            method: 'wallet_switchEthereumChain',
            params: [{ chainId: HARDHAT_NETWORK.chainId }],
        });
    } catch (e) {
        if (e.code === 4902) {
            await window.ethereum.request({
                method: 'wallet_addEthereumChain',
                params: [HARDHAT_NETWORK],
            });
        } else {
            throw new Error(`Basculez MetaMask sur Hardhat Local (chainId ${expectedChainId})`);
        }
    }
}

export async function connectMetaMask(options = {}) {
    if (!hasMetaMask()) {
        throw new Error('MetaMask n\'est pas installé. Ajoutez l\'extension depuis metamask.io');
    }

    if (options.ensureChainId) {
        await ensureHardhatNetwork(options.ensureChainId);
    }

    const provider = new BrowserProvider(window.ethereum);
    await provider.send('eth_requestAccounts', []);
    const signer = await provider.getSigner();
    const address = await signer.getAddress();
    const network = await provider.getNetwork();

    return {
        provider,
        signer,
        address: address.toLowerCase(),
        chainId: Number(network.chainId),
    };
}

export async function signMessage(signer, message) {
    return signer.signMessage(message);
}

export async function getConnectedAddress() {
    if (!hasMetaMask()) return null;

    const provider = new BrowserProvider(window.ethereum);
    const accounts = await provider.send('eth_accounts', []);

    return accounts[0]?.toLowerCase() ?? null;
}

export function shortenAddress(address) {
    if (!address) return '';
    return `${address.slice(0, 6)}...${address.slice(-4)}`;
}

export function setupMetaMaskListeners(onAccountsChanged, onChainChanged) {
    if (!hasMetaMask()) return;
    window.ethereum.on('accountsChanged', onAccountsChanged);
    window.ethereum.on('chainChanged', onChainChanged);
}
