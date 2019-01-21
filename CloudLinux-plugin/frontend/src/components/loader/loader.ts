export default function useLoader(target: any, propertyKey: string, descriptor: TypedPropertyDescriptor<any>) {
    let originalMethod = descriptor.value;
    descriptor.value = async function (...args: any[]) {
        let loader = (this as any).$root.$children[0].$refs.loader;

        loader.isLoading = true;
        let result;
        try {
            result = await originalMethod.apply(this, args);
        } catch (e) {
            throw e;
        } finally {
            loader.isLoading = false;
        }
        return result;
    };

    return descriptor;
}
