| branch  | status |
| :------------- | :------------- |
| main | [![tests](https://github.com/martapavelka/scpc/actions/workflows/test.yml/badge.svg?branch=main)](https://github.com/martapavelka/scpc/actions/workflows/test.yml) |
| dev | [![tests](https://github.com/martapavelka/scpc/actions/workflows/test.yml/badge.svg?branch=dev)](https://github.com/martapavelka/scpc/actions/workflows/test.yml) |

# Simplicial Complex Property Check (SCPC)

The script detects the simplicial complex closure properties from STDIN. Outputs labeling if property is detected. See exit codes below.

## Usage: Google Collab 

- [colab.research.google.com/.../dev](https://colab.research.google.com/github/martapavelka/scpc/blob/dev/scpc.ipynb)

## Usage: Web UI

- [https://detector.martapavelka.com/](https://detector.martapavelka.com/)

## Local usage

- Compile and set permissions
    
    ```sh
    wget https://raw.githubusercontent.com/martapavelka/scpc/dev/scpc.ipynb
    jupyter nbconvert --to python scpc.ipynb
    chmod +x scpc.py
    ```
- Run the script, e.g.
    
    ```sh
    echo "1 2 3
    4 5 6
    7 8 9" | ./scpc.py --property under-closed
    ```

## Exit Codes

- `0` Property detected
- `1` Unexpected exception
- `2` Invalid usage
- `3` Does not have property
